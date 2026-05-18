<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class FaceitNormalizer
{
    public function toMatchPayload(array $details, array $stats): array
    {
        $roundStats = $stats['round_stats'] ?? [];
        $scoreRaw = $roundStats['Score'] ?? '0 / 0';
        $scoreParts = array_map('trim', explode(' / ', $scoreRaw));
        $teamAScore = (int) ($scoreParts[0] ?? 0);
        $teamBScore = (int) ($scoreParts[1] ?? 0);

        $startedAt = $details['started_at'] ?? 0;
        $finishedAt = $details['finished_at'] ?? 0;
        $durationMinutes = ($startedAt && $finishedAt)
            ? (int) round(($finishedAt - $startedAt) / 60)
            : null;

        $outcome = match (true) {
            $teamAScore > $teamBScore => 'team_a',
            $teamBScore > $teamAScore => 'team_b',
            default => 'draw',
        };

        return [
            'faceit_match_id' => $details['match_id'],
            'game' => 'cs2',
            'map' => $roundStats['Map'] ?? null,
            'team_a_name' => $details['teams']['faction1']['name'] ?? null,
            'team_b_name' => $details['teams']['faction2']['name'] ?? null,
            'team_a_score' => $teamAScore,
            'team_b_score' => $teamBScore,
            'duration_minutes' => $durationMinutes,
            'outcome' => $outcome,
            'played_at' => $finishedAt ? Carbon::createFromTimestamp($finishedAt) : null,
            'raw_faceit_payload' => $details,
        ];
    }

    public function toPlayerPayloads(array $stats, array $details): array
    {
        $teams = $stats['teams'] ?? [];
        $players = [];

        foreach ($teams as $index => $team) {
            $teamLetter = $index === 0 ? 'a' : 'b';

            $teamScore = (int) ($team['team_stats']['Final Score'] ?? 0);
            $opponentScore = (int) ($teams[$index === 0 ? 1 : 0]['team_stats']['Final Score'] ?? 0);
            $result = $teamScore > $opponentScore ? 'win' : 'loss';

            foreach ($team['players'] ?? [] as $player) {
                $ps = $player['player_stats'] ?? [];
                $kills = (int) ($ps['Kills'] ?? 0);
                $deaths = (int) ($ps['Deaths'] ?? 0);
                $assists = (int) ($ps['Assists'] ?? 0);
                $adr = (float) ($ps['ADR'] ?? 0);
                $rounds = (int) ($stats['round_stats']['Rounds'] ?? 0);

                $players[] = [
                    'faceit_player_id' => $player['player_id'],
                    'faceit_nickname' => $player['nickname'],
                    'team' => $teamLetter,
                    'result' => $result,
                    'kills' => $kills,
                    'deaths' => $deaths,
                    'assists' => $assists,
                    'kda' => (float) ($ps['K/D Ratio'] ?? 0),
                    'damage_dealt' => $rounds > 0 ? (int) round($adr * $rounds) : 0,
                    'hs_percent' => (float) ($ps['Headshots %'] ?? 0),
                    'headshots' => (int) ($ps['Headshots'] ?? 0),
                    'utility_damage' => 0,
                    'adr' => $adr,
                    'mvp_count' => (int) ($ps['MVPs'] ?? 0),
                    'clutches_won' => (int) ($ps['1v1Wins'] ?? 0) + (int) ($ps['1v2Wins'] ?? 0),
                    'rating' => isset($ps['HLTV Rating']) ? (float) $ps['HLTV Rating'] : null,
                ];
            }
        }

        return $players;
    }
}
