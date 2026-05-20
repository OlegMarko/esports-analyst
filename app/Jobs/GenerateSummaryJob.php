<?php

namespace App\Jobs;

use App\Ai\Agents\MatchAnalystAgent;
use App\Events\MatchSummaryReady;
use App\Models\GameMatch;
use App\Services\ContextCompressor;
use App\Services\HybridMatchRetriever;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Reranking;
use Laravel\Ai\Responses\StructuredAgentResponse;

class GenerateSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly int $matchId)
    {
        $this->onQueue('summaries');
    }

    public function handle(HybridMatchRetriever $retriever, ContextCompressor $compressor): void
    {
        $match = GameMatch::with('players')->findOrFail($this->matchId);

        // Hybrid retrieval: vector + keyword search, fused by RRF
        $candidates = $retriever->retrieve(
            "{$match->map} {$match->team_a_name} {$match->team_b_name}",
            $match->game,
            20
        );

        // Rerank summaries for semantic relevance, map indexes back to Match models
        $candidatesWithSummary = $candidates
            ->filter(fn ($m) => ! empty($m->ai_summary))
            ->values();

        $topMatches = [];

        if ($candidatesWithSummary->isNotEmpty()) {
            $canRerank = filled(config('ai.providers.cohere.key'));

            if ($canRerank) {
                // Cohere trial: 10 req/min — allow max 5 to leave ample headroom; decay over 60s
                if (! RateLimiter::attempt('cohere-rerank', 5, fn () => null, 60)) {
                    $this->release(RateLimiter::availableIn('cohere-rerank') + 10);
                    return;
                }

                try {
                    $reranked = Reranking::of($candidatesWithSummary->pluck('ai_summary')->all())
                        ->limit(5)
                        ->rerank("{$match->map} cs2 match analysis");

                    $topMatches = $reranked->collect()
                        ->map(fn ($ranked) => $candidatesWithSummary[$ranked->index] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                } catch (RateLimitedException) {
                    $this->release(75);
                    return;
                } catch (\Throwable) {
                    $topMatches = $candidatesWithSummary->take(5)->all();
                }
            } else {
                $topMatches = $candidatesWithSummary->take(5)->all();
            }
        }

        // Compress similar match summaries into concise context
        $context = $compressor->compress($match->map ?? 'cs2', collect($topMatches));

        $winner = match ($match->winner) {
            'a'    => $match->team_a_name,
            'b'    => $match->team_b_name,
            default => 'Draw',
        };

        $teamAPlayers = $match->players->where('team', 'a')->sortByDesc('kda');
        $teamBPlayers = $match->players->where('team', 'b')->sortByDesc('kda');

        $formatTeam = fn ($players) => $players->map(fn ($p) =>
            "  {$p->faceit_nickname}: {$p->kills}K/{$p->deaths}D/{$p->assists}A "
            . "KDA:{$p->kda} ADR:{$p->adr} HS:{$p->hs_percent}% Clutches:{$p->clutches_won}"
        )->join("\n");

        $prompt = "Analyse this CS2 Faceit match and fill every output field:\n\n"
            . "Map: {$match->map}\n"
            . "Winner: {$winner}\n"
            . "Final score: {$match->team_a_name} {$match->team_a_score} – {$match->team_b_score} {$match->team_b_name}\n"
            . "Half-time: {$match->first_half_score} (first) / {$match->second_half_score} (second)\n"
            . ($match->duration_minutes ? "Duration: {$match->duration_minutes} minutes\n" : '')
            . "\n{$match->team_a_name} (sorted by KDA):\n" . $formatTeam($teamAPlayers)
            . "\n\n{$match->team_b_name} (sorted by KDA):\n" . $formatTeam($teamBPlayers)
            . "\n\nSimilar historical matches for context:\n" . $context;

        /** @var StructuredAgentResponse $response */
        $response = MatchAnalystAgent::make(game: $match->game)->prompt($prompt);

        $str = fn ($v) => (is_string($v) && strtolower(trim($v)) === 'null') ? null : ($v ?: null);

        $match->update([
            'headline'          => $str($response['headline'] ?? null),
            'ai_summary'        => $str($response['summary'] ?? null),
            'key_moments'       => $response['key_moments'] ?? null ?: null,
            'mvp'               => $str($response['mvp'] ?? null),
            'economy_rating'    => $response['economy_rating'] ?? null,
            'mechanical_rating' => $response['mechanical_rating'] ?? null,
            'match_score'       => $response['score'] ?? null,
            'similar_match_ids' => $response['similar_match_ids'] ?? null ?: null,
            'summary_at'        => now(),
        ]);

        UpdateLeaderboardsJob::dispatch($match->id)->onQueue('webhooks');

        broadcast(new MatchSummaryReady($match->id, $response->toArray()));
    }
}
