<?php

namespace App\Jobs;

use App\Models\GameMatch;
use App\Models\TrackedPlayer;
use App\Services\LeaderboardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateLeaderboardsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $matchId)
    {
        $this->onQueue('webhooks');
    }

    public function handle(LeaderboardService $leaderboard): void
    {
        $match = GameMatch::with('players')->findOrFail($this->matchId);

        $playerIds = $match->players->pluck('faceit_player_id')->filter()->all();
        $tracked   = TrackedPlayer::whereIn('faceit_id', $playerIds)->get()->keyBy('faceit_id');

        foreach ($match->players as $player) {
            $leaderboard->record($player, $match->game, 'kda', (float) $player->kda);
            $leaderboard->record($player, $match->game, 'frags', (float) $player->kills);
            $leaderboard->record($player, $match->game, 'adr', (float) $player->adr);
            $leaderboard->record($player, $match->game, 'clutches_won', (float) $player->clutches_won);

            if ($tp = $tracked->get($player->faceit_player_id)) {
                GeneratePlayerBriefJob::dispatch($tp->id)->onQueue('summaries');
            }
        }
    }
}
