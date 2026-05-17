<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'match_id',
        'faceit_player_id',
        'faceit_nickname',
        'team',
        'result',
        'kda',
        'kills',
        'deaths',
        'assists',
        'damage_dealt',
        'hs_percent',
        'headshots',
        'utility_damage',
        'clutches_won',
        'mvp_count',
        'adr',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'kda' => 'float',
            'hs_percent' => 'float',
            'adr' => 'float',
            'rating' => 'float',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function getKdaLabelAttribute(): string
    {
        return "{$this->kills}/{$this->deaths}/{$this->assists}";
    }
}
