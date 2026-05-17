<?php

namespace App\Models;

use Database\Factories\TrackedPlayerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackedPlayer extends Model
{
    /** @use HasFactory<TrackedPlayerFactory> */
    use HasFactory;

    protected $fillable = [
        'faceit_id',
        'faceit_nickname',
        'steam_id',
        'avatar',
        'faceit_level',
        'elo',
        'active',
        'last_polled_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_polled_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
