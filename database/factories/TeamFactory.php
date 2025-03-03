<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company() . ' FC',
            'league' => fake()->randomElement(['A', 'B']),
        ];
    }

    public function leagueA(): self
    {
        return $this->state(fn (array $attributes) => [
            'league' => 'A',
        ]);
    }

    public function leagueB(): self
    {
        return $this->state(fn (array $attributes) => [
            'league' => 'B',
        ]);
    }
}
