<?php

namespace App\Jobs;

use App\Models\LiveMatch;
use App\Services\FaceitService;
use App\Services\PredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessFaceitWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly array $payload) {}

    public function handle(FaceitService $faceit, PredictionService $predictor): void
    {
        $event   = $this->payload['event'] ?? '';
        $match   = $this->payload['payload'] ?? [];
        $matchId = $match['id'] ?? null;

        if (! $matchId) {
            return;
        }

        match ($event) {
            'match_status_ready',
            'match_status_ongoing'   => $this->upsertLive($matchId, $match, $faceit, $predictor),

            'match_status_finished',
            'match_status_cancelled',
            'match_status_aborted'   => $this->cleanup($matchId),

            default => null,
        };
    }

    private function upsertLive(string $matchId, array $match, FaceitService $faceit, PredictionService $predictor): void
    {
        $teams   = $match['teams'] ?? [];
        $teamA   = $teams[0] ?? [];
        $teamB   = $teams[1] ?? [];

        $rosterA = $teamA['roster'] ?? [];
        $rosterB = $teamB['roster'] ?? [];

        if (empty($rosterA) || empty($rosterB)) {
            return;
        }

        $teamAIds    = array_column($rosterA, 'id');
        $teamBIds    = array_column($rosterB, 'id');
        $teamALevels = array_column(array_combine($teamAIds, $rosterA), 'game_skill_level', 'id');
        $teamBLevels = array_column(array_combine($teamBIds, $rosterB), 'game_skill_level', 'id');

        $prediction = $predictor->predict($teamAIds, $teamBIds, $teamALevels + $teamBLevels);

        // Try to get the map — not in webhook payload, requires one API call
        $map = null;
        try {
            $details = $faceit->matchDetails($matchId);
            $map = $details['voting']['map']['pick'][0]
                ?? $details['voting']['map']['entities'][0]['guid']
                ?? null;
        } catch (\Exception $e) {
            Log::debug("Could not fetch map for webhook match {$matchId}: {$e->getMessage()}");
        }

        $roster = fn (array $players) => array_map(
            fn ($p) => [
                'player_id'   => $p['id'],
                'nickname'    => $p['nickname'],
                'skill_level' => $p['game_skill_level'] ?? null,
            ],
            $players
        );

        LiveMatch::updateOrCreate(
            ['faceit_match_id' => $matchId],
            [
                'game'             => $match['game'] ?? 'cs2',
                'status'           => $this->payload['event'] === 'match_status_ongoing' ? 'ONGOING' : 'READY',
                'map'              => $map,
                'team_a_name'      => $teamA['name'] ?? 'Team A',
                'team_b_name'      => $teamB['name'] ?? 'Team B',
                'team_a_roster'    => $roster($rosterA),
                'team_b_roster'    => $roster($rosterB),
                'team_a_win_prob'  => $prediction['team_a_win_prob'],
                'team_b_win_prob'  => $prediction['team_b_win_prob'],
                'team_a_elo_avg'   => $prediction['team_a_elo_avg'],
                'team_b_elo_avg'   => $prediction['team_b_elo_avg'],
                'confidence'       => $prediction['confidence'],
                'margin_of_error'  => $prediction['margin_of_error'],
                'prediction_basis' => $prediction['prediction_basis'],
                'started_at'       => isset($match['started_at'])
                    ? Carbon::parse($match['started_at'])
                    : null,
                'fetched_at'       => now(),
            ]
        );
    }

    private function cleanup(string $matchId): void
    {
        LiveMatch::where('faceit_match_id', $matchId)->delete();

        // Trigger immediate ingestion — stats need ~3 min to appear on Faceit API
        PollFaceitMatchesJob::dispatch()->onQueue('webhooks')->delay(now()->addMinutes(3));
    }
}
