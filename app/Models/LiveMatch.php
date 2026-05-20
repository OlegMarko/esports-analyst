<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveMatch extends Model
{
    protected $fillable = [
        'faceit_match_id',
        'game',
        'status',
        'map',
        'team_a_name',
        'team_b_name',
        'team_a_roster',
        'team_b_roster',
        'team_a_elo_avg',
        'team_b_elo_avg',
        'team_a_win_prob',
        'team_b_win_prob',
        'confidence',
        'margin_of_error',
        'prediction_basis',
        'started_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'team_a_roster' => 'array',
            'team_b_roster' => 'array',
            'started_at'    => 'datetime',
            'fetched_at'    => 'datetime',
        ];
    }
}
