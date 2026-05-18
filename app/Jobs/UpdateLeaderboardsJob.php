<?php

namespace App\Jobs;

use App\Models\GameMatch;
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

        foreach ($match->players as $player) {
            $leaderboard->record($player);
        }
    }
}
