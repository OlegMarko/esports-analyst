<?php

namespace App\Jobs;

use App\Models\GameMatch;
use App\Models\LiveMatch;
use App\Models\TrackedPlayer;
use App\Services\FaceitService;
use App\Services\PredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PollLiveMatchesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('webhooks');
    }

    public function handle(FaceitService $faceit, PredictionService $predictor): void
    {
        $seen = [];

        // --- Collect hub IDs: manual config + auto-discovered from player history ---
        $hubIds = $this->resolveHubIds($faceit);

        // --- Hub-based detection (works for hub-type competitions) ---
        foreach ($hubIds as $hubId) {
            try {
                foreach ($faceit->hubMatches($hubId, limit: 20) as $details) {
                    $this->processDetails($details, $predictor, $seen);
                }
            } catch (\Exception $e) {
                Log::warning("Hub live poll failed for hub {$hubId}: {$e->getMessage()}");
            }
        }

        // --- Player-based detection (catches any non-hub match that slipped through) ---
        foreach (TrackedPlayer::active()->get() as $tracked) {
            try {
                foreach ($faceit->playerMatches($tracked->faceit_id, limit: 3) as $item) {
                    $matchId = $item['match_id'] ?? null;
                    if (! $matchId || isset($seen[$matchId])) {
                        continue;
                    }

                    if (GameMatch::where('faceit_match_id', $matchId)->exists()) {
                        continue;
                    }

                    $cacheKey = "live_status:{$matchId}";
                    if (Cache::get($cacheKey) === 'FINISHED') {
                        continue;
                    }

                    $this->processDetails($faceit->matchDetails($matchId), $predictor, $seen);
                }
            } catch (\Exception $e) {
                Log::warning("Player live poll failed for {$tracked->faceit_nickname}: {$e->getMessage()}");
            }
        }

        // --- Cleanup ---
        $finished = GameMatch::whereIn(
            'faceit_match_id',
            LiveMatch::pluck('faceit_match_id')->all()
        )->pluck('faceit_match_id')->all();

        if (! empty($finished)) {
            LiveMatch::whereIn('faceit_match_id', $finished)->delete();
        }

        LiveMatch::where('updated_at', '<', now()->subHour())->delete();
    }

    /**
     * Merge manually configured hub IDs with ones auto-discovered from
     * tracked players' match history (hub-type competitions only).
     * Result is cached for 1 hour to avoid re-scanning history each minute.
     */
    private function resolveHubIds(FaceitService $faceit): array
    {
        $manual = array_filter(array_map('trim', explode(',', config('services.faceit.hub_ids', ''))));

        $discovered = Cache::remember('live:auto_hub_ids', 3600, function () use ($faceit) {
            $ids = [];
            foreach (TrackedPlayer::active()->get() as $tracked) {
                try {
                    foreach ($faceit->playerMatches($tracked->faceit_id, limit: 20) as $item) {
                        if (($item['competition_type'] ?? '') === 'hub') {
                            $ids[] = $item['competition_id'];
                        }
                    }
                } catch (\Exception) {
                }
            }
            return array_values(array_unique($ids));
        });

        return array_values(array_unique(array_merge($manual, $discovered)));
    }

    private function processDetails(array $details, PredictionService $predictor, array &$seen): void
    {
        $matchId = $details['match_id'] ?? null;
        if (! $matchId || isset($seen[$matchId])) {
            return;
        }
        $seen[$matchId] = true;

        if (GameMatch::where('faceit_match_id', $matchId)->exists()) {
            return;
        }

        $status = $details['status'] ?? 'UNKNOWN';

        if (in_array($status, ['FINISHED', 'CANCELLED', 'ABORTED'])) {
            Cache::put("live_status:{$matchId}", 'FINISHED', now()->addHours(6));
            return;
        }

        if (! in_array($status, ['ONGOING', 'READY', 'SCHEDULED'])) {
            return;
        }

        $faction1 = $details['teams']['faction1'] ?? [];
        $faction2 = $details['teams']['faction2'] ?? [];
        $teamAIds = array_column($faction1['roster'] ?? [], 'player_id');
        $teamBIds = array_column($faction2['roster'] ?? [], 'player_id');

        $prediction = $predictor->predict($teamAIds, $teamBIds);

        $map = $details['voting']['map']['pick'][0]
            ?? $details['voting']['map']['entities'][0]['guid']
            ?? null;

        LiveMatch::updateOrCreate(
            ['faceit_match_id' => $matchId],
            [
                'game'             => $details['game'] ?? 'cs2',
                'status'           => $status,
                'map'              => $map,
                'team_a_name'      => $faction1['name'] ?? 'Team A',
                'team_b_name'      => $faction2['name'] ?? 'Team B',
                'team_a_roster'    => $faction1['roster'] ?? [],
                'team_b_roster'    => $faction2['roster'] ?? [],
                'team_a_win_prob'  => $prediction['team_a_win_prob'],
                'team_b_win_prob'  => $prediction['team_b_win_prob'],
                'team_a_elo_avg'   => $prediction['team_a_elo_avg'],
                'team_b_elo_avg'   => $prediction['team_b_elo_avg'],
                'confidence'       => $prediction['confidence'],
                'margin_of_error'  => $prediction['margin_of_error'],
                'prediction_basis' => $prediction['prediction_basis'],
                'started_at'       => isset($details['started_at'])
                    ? Carbon::createFromTimestamp($details['started_at'])
                    : null,
                'fetched_at'       => now(),
            ]
        );
    }
}
