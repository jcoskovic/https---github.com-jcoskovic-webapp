<?php

namespace Database\Factories;

use App\Models\Abbreviation;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vote>
 */
class VoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'abbreviation_id' => Abbreviation::factory(),
            'type' => $this->faker->randomElement(['up', 'down']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the vote is an upvote.
     */
    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'up',
        ]);
    }

    /**
     * Indicate that the vote is a downvote.
     */
    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'down',
        ]);
    }
}
