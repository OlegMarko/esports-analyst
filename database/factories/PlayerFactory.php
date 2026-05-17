<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    public function definition(): array
    {
        $kills = fake()->numberBetween(5, 35);
        $deaths = fake()->numberBetween(5, 25);
        $assists = fake()->numberBetween(0, 15);
        $kda = $deaths > 0 ? round(($kills + $assists * 0.5) / $deaths, 2) : $kills + $assists * 0.5;
        $hsPercent = fake()->randomFloat(1, 30, 70);

        return [
            'match_id' => GameMatch::factory(),
            'faceit_player_id' => Str::uuid()->toString(),
            'faceit_nickname' => fake()->userName(),
            'team' => fake()->randomElement(['a', 'b']),
            'result' => fake()->randomElement(['win', 'loss']),
            'kda' => $kda,
            'kills' => $kills,
            'deaths' => $deaths,
            'assists' => $assists,
            'damage_dealt' => fake()->numberBetween(1000, 5000),
            'hs_percent' => $hsPercent,
            'headshots' => (int) round($kills * $hsPercent / 100),
            'utility_damage' => fake()->numberBetween(0, 500),
            'clutches_won' => fake()->numberBetween(0, 5),
            'mvp_count' => fake()->numberBetween(0, 8),
            'adr' => fake()->randomFloat(1, 50, 120),
            'rating' => fake()->optional(0.7)->randomFloat(2, 0.5, 2.0),
        ];
    }
}
