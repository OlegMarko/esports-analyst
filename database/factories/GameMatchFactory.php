<?php

namespace Database\Factories;

use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        $maps = ['de_mirage', 'de_inferno', 'de_dust2', 'de_nuke', 'de_ancient', 'de_anubis'];
        $teamAScore = fake()->numberBetween(0, 16);
        $teamBScore = $teamAScore === 16 ? fake()->numberBetween(0, 14) : 16;

        return [
            'faceit_match_id' => Str::uuid()->toString(),
            'game' => 'cs2',
            'map' => fake()->randomElement($maps),
            'team_a_name' => fake()->lastName() . ' Esports',
            'team_b_name' => fake()->lastName() . ' Gaming',
            'team_a_score' => $teamAScore,
            'team_b_score' => $teamBScore,
            'duration_minutes' => fake()->numberBetween(30, 90),
            'outcome' => $teamAScore > $teamBScore ? 'team_a' : 'team_b',
            'played_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'playstyle_tags' => null,
            'eco_round_count' => fake()->numberBetween(0, 8),
            'first_half_score' => fake()->numberBetween(0, 12) . '-' . fake()->numberBetween(0, 12),
            'second_half_score' => fake()->numberBetween(0, 9) . '-' . fake()->numberBetween(0, 9),
            'ai_summary' => null,
            'summary_at' => null,
            'raw_faceit_payload' => null,
        ];
    }
}
