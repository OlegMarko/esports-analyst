<?php

namespace App\Models;

use Database\Factories\GameMatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'faceit_match_id',
        'game',
        'map',
        'team_a_name',
        'team_b_name',
        'team_a_score',
        'team_b_score',
        'duration_minutes',
        'outcome',
        'played_at',
        'playstyle_tags',
        'eco_round_count',
        'first_half_score',
        'second_half_score',
        'headline',
        'ai_summary',
        'key_moments',
        'mvp',
        'economy_rating',
        'mechanical_rating',
        'match_score',
        'similar_match_ids',
        'summary_at',
        'raw_faceit_payload',
    ];

    protected function casts(): array
    {
        return [
            'playstyle_tags'    => 'array',
            'key_moments'       => 'array',
            'similar_match_ids' => 'array',
            'raw_faceit_payload' => 'array',
            'played_at'  => 'datetime',
            'summary_at' => 'datetime',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'match_id');
    }

    public function matchAspectEmbeddings(): HasMany
    {
        return $this->hasMany(MatchAspectEmbedding::class, 'match_id');
    }

    public function scopeForGame(Builder $query, string $game): Builder
    {
        return $query->where('game', $game);
    }

    public function getWinnerAttribute(): string
    {
        if ($this->team_a_score > $this->team_b_score) {
            return 'a';
        }

        if ($this->team_b_score > $this->team_a_score) {
            return 'b';
        }

        return 'draw';
    }
}
