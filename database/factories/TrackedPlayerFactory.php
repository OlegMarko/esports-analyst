<?php

namespace Database\Factories;

use App\Models\TrackedPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrackedPlayer>
 */
class TrackedPlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faceit_id' => Str::uuid()->toString(),
            'faceit_nickname' => fake()->userName(),
            'steam_id' => '7656119' . fake()->numerify('##########'),
            'avatar' => fake()->optional()->imageUrl(184, 184, 'people'),
            'faceit_level' => fake()->numberBetween(1, 10),
            'elo' => fake()->numberBetween(500, 3000),
            'active' => true,
            'last_polled_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
