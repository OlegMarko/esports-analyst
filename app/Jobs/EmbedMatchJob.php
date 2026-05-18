<?php

namespace App\Jobs;

use App\Models\GameMatch;
use App\Models\MatchAspectEmbedding;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Embeddings;

class EmbedMatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly int $matchId)
    {
        $this->onQueue('embeddings');
    }

    public function handle(): void
    {
        $match = GameMatch::with('players')->findOrFail($this->matchId);

        foreach ($this->buildAspectTexts($match) as $aspect => $text) {
            $score = $this->scoreAspect($match, $aspect);
            $vector = Embeddings::for([$text])->cache(86400)->generate()->first();

            MatchAspectEmbedding::updateOrCreate(
                ['match_id' => $match->id, 'aspect' => $aspect],
                ['embedding' => $vector, 'game' => $match->game, 'aspect_score' => $score],
            );
        }
    }

    /** @return array<string, string> */
    private function buildAspectTexts(GameMatch $match): array
    {
        $playerStats = $match->players->map(
            fn ($p) => "{$p->faceit_nickname} {$p->kills}K/{$p->deaths}D/{$p->assists}A KDA:{$p->kda} ADR:{$p->adr} HS:{$p->hs_percent}%"
        )->join(', ');

        $mvps = $match->players->map(fn ($p) => "{$p->faceit_nickname}:{$p->mvp_count}")->join(', ');
        $clutches = $match->players->map(fn ($p) => "{$p->faceit_nickname}:{$p->clutches_won}")->join(', ');
        $topFragger = $match->players->sortByDesc('kills')->first();

        return [
            'economy' => "CS2 economy analysis on {$match->map}. "
                . "Team A score: {$match->team_a_score}, Team B score: {$match->team_b_score}. "
                . "Eco rounds played: {$match->eco_round_count}. "
                . "First half: {$match->first_half_score}, Second half: {$match->second_half_score}.",

            'mechanics' => "Individual mechanical performance on {$match->map}. "
                . "Players: {$playerStats}",

            'teamplay' => "Team coordination on {$match->map}. "
                . "Team A ({$match->team_a_name}): score {$match->team_a_score}. "
                . "Team B ({$match->team_b_name}): score {$match->team_b_score}. "
                . "MVPs: {$mvps} "
                . "Clutches: {$clutches}",

            'momentum' => "Match momentum and flow on {$match->map}. "
                . "Final: {$match->team_a_score}-{$match->team_b_score}. "
                . "Half scores: {$match->first_half_score} / {$match->second_half_score}. "
                . "Top fragger: {$topFragger?->faceit_nickname} "
                . "with {$topFragger?->kills} kills.",
        ];
    }

    private function scoreAspect(GameMatch $match, string $aspect): float
    {
        return match ($aspect) {
            'economy' => min(1.0, $match->eco_round_count / 10),
            'mechanics' => min(1.0, (float) ($match->players->avg('kda') ?? 0) / 2.0),
            'teamplay' => min(1.0, (float) ($match->players->avg('mvp_count') ?? 0) / 5.0),
            'momentum' => min(1.0, abs($match->team_a_score - $match->team_b_score) / 16.0),
            default => 0.5,
        };
    }
}
