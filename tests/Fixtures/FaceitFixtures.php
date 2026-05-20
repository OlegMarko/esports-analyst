<?php

function faceitMatchDetailsFixture(string $matchId = 'match-abc-123', string $status = 'FINISHED'): array
{
    return [
        'match_id' => $matchId,
        'status' => $status,
        'game' => 'cs2',
        'started_at' => 1747000000,
        'finished_at' => 1747003600,
        'teams' => [
            'faction1' => [
                'faction_id' => 'team-001',
                'name' => 'Alpha Squad',
                'roster' => array_map(
                    fn ($i) => ['player_id' => "player-a-{$i}", 'nickname' => "playerA{$i}"],
                    range(1, 5)
                ),
            ],
            'faction2' => [
                'faction_id' => 'team-002',
                'name' => 'Bravo Force',
                'roster' => array_map(
                    fn ($i) => ['player_id' => "player-b-{$i}", 'nickname' => "playerB{$i}"],
                    range(1, 5)
                ),
            ],
        ],
    ];
}

function faceitMatchStatsFixture(string $map = 'de_mirage', int $teamAScore = 16, int $teamBScore = 14): array
{
    $totalRounds = $teamAScore + $teamBScore;

    $makePlayers = fn (string $prefix, int $teamScore, int $oppScore) => array_map(
        function ($i) use ($prefix, $teamScore, $oppScore) {
            $kills = random_int(10, 30);
            $deaths = random_int(8, 22);
            $assists = random_int(0, 10);
            $adr = round(random_int(50, 110) + random_int(0, 9) * 0.1, 1);

            return [
                'player_id' => "{$prefix}-{$i}",
                'nickname' => "{$prefix}{$i}",
                'player_stats' => [
                    'Kills' => (string) $kills,
                    'Deaths' => (string) $deaths,
                    'Assists' => (string) $assists,
                    'K/D Ratio' => (string) round($kills / max($deaths, 1), 2),
                    'Headshots' => (string) (int) ($kills * 0.45),
                    'Headshots %' => '45',
                    'ADR' => (string) $adr,
                    'MVPs' => (string) random_int(0, 5),
                    '1v1Wins' => (string) random_int(0, 3),
                    '1v2Wins' => (string) random_int(0, 1),
                    'HLTV Rating' => (string) round(0.8 + random_int(0, 80) * 0.01, 2),
                ],
            ];
        },
        range(1, 5)
    );

    return [
        'round_stats' => [
            'Map' => $map,
            'Score' => "{$teamAScore} / {$teamBScore}",
            'Rounds' => (string) $totalRounds,
            'Winner' => $teamAScore > $teamBScore ? 'faction1' : 'faction2',
        ],
        'teams' => [
            [
                'team_id' => 'team-001',
                'team_stats' => ['Final Score' => (string) $teamAScore],
                'players' => $makePlayers('player-a', $teamAScore, $teamBScore),
            ],
            [
                'team_id' => 'team-002',
                'team_stats' => ['Final Score' => (string) $teamBScore],
                'players' => $makePlayers('player-b', $teamBScore, $teamAScore),
            ],
        ],
    ];
}
