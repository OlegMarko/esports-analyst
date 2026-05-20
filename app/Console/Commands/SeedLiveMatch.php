<?php

namespace App\Console\Commands;

use App\Models\LiveMatch;
use Illuminate\Console\Command;

class SeedLiveMatch extends Command
{
    protected $signature = 'faceit:live-seed';

    protected $description = 'Insert a fake live match for UI testing';

    public function handle(): void
    {
        LiveMatch::updateOrCreate(
            ['faceit_match_id' => 'test-match-001'],
            [
                'game'             => 'cs2',
                'status'           => 'ONGOING',
                'map'              => 'de_mirage',
                'team_a_name'      => 'Alpha Squad',
                'team_b_name'      => 'Bravo Force',
                'team_a_roster'    => [
                    ['player_id' => 'p1', 'nickname' => 's1mple'],
                    ['player_id' => 'p2', 'nickname' => 'NiKo'],
                    ['player_id' => 'p3', 'nickname' => 'device'],
                    ['player_id' => 'p4', 'nickname' => 'ZywOo'],
                    ['player_id' => 'p5', 'nickname' => 'sh1ro'],
                ],
                'team_b_roster'    => [
                    ['player_id' => 'p6', 'nickname' => 'electroNic'],
                    ['player_id' => 'p7', 'nickname' => 'hobbit'],
                    ['player_id' => 'p8', 'nickname' => 'Perfecto'],
                    ['player_id' => 'p9', 'nickname' => 'Boombl4'],
                    ['player_id' => 'p10', 'nickname' => 'b1t'],
                ],
                'team_a_elo_avg'   => 2450,
                'team_b_elo_avg'   => 2310,
                'team_a_win_prob'  => 0.61,
                'team_b_win_prob'  => 0.39,
                'confidence'       => 0.72,
                'margin_of_error'  => 8,
                'prediction_basis' => '5 tracked / 5 estimated',
                'started_at'       => now()->subMinutes(14),
                'fetched_at'       => now(),
            ]
        );

        $this->info('Fake live match inserted — visit /live to preview.');
    }
}
