<?php

namespace App\Jobs;

use App\Ai\Agents\PlayerAnalystAgent;
use App\Models\Player;
use App\Models\TrackedPlayer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Enums\Lab;

class GeneratePlayerBriefJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public readonly int $trackedPlayerId)
    {
        $this->onQueue('summaries');
    }

    public function handle(): void
    {
        $player = TrackedPlayer::findOrFail($this->trackedPlayerId);

        $recentMatches = Player::where('faceit_player_id', $player->faceit_id)
            ->with('match')
            ->join('matches', 'players.match_id', '=', 'matches.id')
            ->orderByDesc('matches.played_at')
            ->select('players.*')
            ->limit(20)
            ->get();

        if ($recentMatches->isEmpty()) {
            return;
        }

        $wins   = $recentMatches->where('result', 'win')->count();
        $avgKda = round($recentMatches->avg('kda'), 2);
        $avgAdr = round((float) $recentMatches->avg('adr'), 1);

        // KDA trend: compare first half vs second half of recent matches (oldest→newest)
        $ordered  = $recentMatches->reverse()->values();
        $half     = (int) ceil($ordered->count() / 2);
        $older    = $ordered->slice(0, $half);
        $newer    = $ordered->slice($half);
        $kdaOld   = round($older->avg('kda'), 2);
        $kdaNew   = round($newer->avg('kda'), 2);
        $trend    = match (true) {
            $kdaNew > $kdaOld + 0.15 => "improving (KDA {$kdaOld} → {$kdaNew})",
            $kdaNew < $kdaOld - 0.15 => "declining (KDA {$kdaOld} → {$kdaNew})",
            default                   => "stable (KDA {$kdaOld} → {$kdaNew})",
        };

        // Per-map breakdown
        $mapStats = $recentMatches
            ->filter(fn ($p) => $p->match?->map)
            ->groupBy(fn ($p) => $p->match->map)
            ->map(fn ($rows, $map) => sprintf(
                "%s: %dW/%dL avg KDA %.2f avg ADR %.1f",
                $map,
                $rows->where('result', 'win')->count(),
                $rows->where('result', 'loss')->count(),
                $rows->avg('kda'),
                $rows->avg('adr'),
            ))
            ->values()
            ->join(', ');

        $matchLines = $recentMatches->take(10)->map(fn ($p) =>
            "- {$p->match?->map}: {$p->kills}K/{$p->deaths}D/{$p->assists}A "
            . "KDA:{$p->kda} ADR:{$p->adr} HS:{$p->hs_percent}% " . ucfirst($p->result)
        )->join("\n");

        $prompt = "Write a 2-3 sentence performance brief for CS2 Faceit player \"{$player->faceit_nickname}\" "
            . "(Level {$player->faceit_level}, {$player->elo} ELO).\n\n"
            . "Last {$recentMatches->count()} matches: {$wins} wins, avg KDA {$avgKda}, avg ADR {$avgAdr}\n"
            . "Form trend: {$trend}\n"
            . "Map breakdown: {$mapStats}\n\n"
            . "Recent match log:\n" . $matchLines;

        $driver   = config('ai.driver');
        $provider = match ($driver) {
            'ollama'    => Lab::Ollama,
            'anthropic' => Lab::Anthropic,
            default     => Lab::Anthropic,
        };
        $model = $driver === 'ollama' ? 'llama3.2' : null;

        $response = PlayerAnalystAgent::make()->prompt($prompt, provider: $provider, model: $model);

        $player->update(['performance_brief' => $response->text]);
    }
}
