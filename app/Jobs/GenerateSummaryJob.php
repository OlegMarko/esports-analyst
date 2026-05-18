<?php

namespace App\Jobs;

use App\Ai\Agents\MatchAnalystAgent;
use App\Events\MatchSummaryReady;
use App\Models\GameMatch;
use App\Services\ContextCompressor;
use App\Services\HybridMatchRetriever;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
            $reranked = Reranking::of($candidatesWithSummary->pluck('ai_summary')->all())
                ->limit(5)
                ->rerank("{$match->map} cs2 match analysis");

            $topMatches = $reranked->collect()
                ->map(fn ($ranked) => $candidatesWithSummary[$ranked->index] ?? null)
                ->filter()
                ->values()
                ->all();
        }

        // Compress similar match summaries into concise context
        $context = $compressor->compress($match->map ?? 'cs2', collect($topMatches));

        $prompt = "Analyse this CS2 Faceit match:\n"
            . "Map: {$match->map}\n"
            . "Teams: {$match->team_a_name} vs {$match->team_b_name}\n"
            . "Final score: {$match->team_a_score} - {$match->team_b_score}\n"
            . "Half scores: {$match->first_half_score} / {$match->second_half_score}\n"
            . "\nPlayer stats:\n"
            . $match->players->map(fn ($p) =>
                "{$p->faceit_nickname} ({$p->team}): {$p->kills}K/{$p->deaths}D/{$p->assists}A "
                . "KDA:{$p->kda} ADR:{$p->adr} HS:{$p->hs_percent}% Clutches:{$p->clutches_won}"
            )->join("\n")
            . "\n\nSimilar historical matches for context:\n"
            . $context;

        /** @var StructuredAgentResponse $response */
        $response = MatchAnalystAgent::make(game: $match->game)->prompt($prompt);

        $match->update([
            'ai_summary' => $response['summary'],
            'summary_at' => now(),
        ]);

        UpdateLeaderboardsJob::dispatch($match->id)->onQueue('webhooks');

        broadcast(new MatchSummaryReady($match->id, $response->toArray()));
    }
}
