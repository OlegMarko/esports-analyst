<?php

namespace App\Services;

use App\Models\Player;
use App\Models\TrackedPlayer;

class PredictionService
{
    // Midpoint ELO per Faceit level bracket
    private const LEVEL_ELO = [1=>500,2=>875,3=>1025,4=>1175,5=>1325,6=>1475,7=>1625,8=>1775,9=>1925,10=>2150];

    public function predict(array $teamAPlayerIds, array $teamBPlayerIds, array $skillLevels = []): array
    {
        $allIds  = array_merge($teamAPlayerIds, $teamBPlayerIds);
        $tracked = TrackedPlayer::whereIn('faceit_id', $allIds)->get()->keyBy('faceit_id');
        $history = Player::whereIn('faceit_player_id', $allIds)
            ->select('faceit_player_id', 'kda', 'result')
            ->get()
            ->groupBy('faceit_player_id');

        $resolveElo = function (string $id) use ($tracked, $history, $skillLevels): ?float {
            // Tier 1: tracked player has real ELO
            if ($tp = $tracked->get($id)) {
                return (float) $tp->elo;
            }
            // Tier 2: derive from match history
            if ($rows = $history->get($id)) {
                $kda     = (float) $rows->avg('kda');
                $winRate = $rows->where('result', 'win')->count() / max($rows->count(), 1);
                return round(1000.0 + ($kda - 1.0) * 250 + ($winRate - 0.5) * 300);
            }
            // Tier 3: Faceit level from webhook roster
            $level = $skillLevels[$id] ?? null;
            if ($level !== null) {
                return (float) (self::LEVEL_ELO[(int) $level] ?? 1000);
            }
            return null;
        };

        $teamStats = function (array $ids) use ($resolveElo): array {
            $elos = array_values(array_filter(array_map($resolveElo, $ids)));
            return [
                'avg'   => !empty($elos) ? array_sum($elos) / count($elos) : 1000.0,
                'count' => count($elos),
            ];
        };

        $a = $teamStats($teamAPlayerIds);
        $b = $teamStats($teamBPlayerIds);

        // Standard ELO win probability
        $eloProbA = 1.0 / (1.0 + 10 ** (($b['avg'] - $a['avg']) / 400.0));

        // Recent form (win rate from match history)
        $formRate = function (array $ids) use ($history): float {
            $wins = 0;
            $total = 0;
            foreach ($ids as $id) {
                if ($rows = $history->get($id)) {
                    $wins  += $rows->where('result', 'win')->count();
                    $total += $rows->count();
                }
            }
            return $total > 0 ? $wins / $total : 0.5;
        };

        $formA     = $formRate($teamAPlayerIds);
        $formB     = $formRate($teamBPlayerIds);
        $formProbA = ($formA + (1.0 - $formB)) / 2.0;

        // 60% ELO weight, 40% form
        $probA = min(0.95, max(0.05, 0.6 * $eloProbA + 0.4 * $formProbA));
        $probB = 1.0 - $probA;

        // Count how many players we have real ELO/history vs level-only
        $withElo      = $a['count'] + $b['count'];
        $total        = count($teamAPlayerIds) + count($teamBPlayerIds);
        $withLevel    = count(array_filter($allIds, fn ($id) => isset($skillLevels[$id]) && !$tracked->has($id) && !$history->has($id)));
        $coverage     = $total > 0 ? $withElo / $total : 0.0;
        $levelCoverage = $total > 0 ? ($withElo + $withLevel) / $total : 0.0;

        // Level-only estimates carry less weight — cap confidence at 0.72
        $confidence = $coverage >= 0.8
            ? round(0.50 + $coverage * 0.40, 2)
            : ($levelCoverage >= 0.8 ? 0.72 : round(0.50 + $levelCoverage * 0.30, 2));

        // Margin of error (binomial 95% CI)
        $effectiveSample = max($withElo + (int) ($withLevel * 0.5), 1);
        $moe = (int) min(20, max(3, round(1.96 * sqrt($probA * $probB / $effectiveSample) * 100)));

        $basis = match (true) {
            $coverage >= 0.8    => 'ELO + recent form',
            $coverage >= 0.4    => 'Partial ELO + form',
            $levelCoverage >= 0.8 => 'Faceit level estimate',
            $levelCoverage >= 0.4 => 'Partial level data',
            default             => 'Limited data',
        };

        return [
            'team_a_win_prob'  => round($probA, 4),
            'team_b_win_prob'  => round($probB, 4),
            'team_a_elo_avg'   => (int) round($a['avg']),
            'team_b_elo_avg'   => (int) round($b['avg']),
            'confidence'       => $confidence,
            'margin_of_error'  => $moe,
            'prediction_basis' => $basis,
        ];
    }
}
