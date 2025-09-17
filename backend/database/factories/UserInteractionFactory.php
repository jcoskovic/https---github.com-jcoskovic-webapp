<?php

namespace Database\Factories;

use App\Models\Abbreviation;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserInteraction>
 */
class UserInteractionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserInteraction::class;

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
            'interaction_type' => $this->faker->randomElement(['view', 'search', 'share', 'bookmark']),
            'interaction_count' => $this->faker->numberBetween(1, 10),
            'last_interaction' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the interaction is a view.
     */
    public function view(): static
    {
        return $this->state(fn (array $attributes) => [
            'interaction_type' => 'view',
        ]);
    }

    /**
     * Indicate that the interaction is a search.
     */
    public function search(): static
    {
        return $this->state(fn (array $attributes) => [
            'interaction_type' => 'search',
        ]);
    }

    /**
     * Indicate that the interaction is a share.
     */
    public function share(): static
    {
        return $this->state(fn (array $attributes) => [
            'interaction_type' => 'share',
        ]);
    }

    /**
     * Indicate that the interaction is a bookmark.
     */
    public function bookmark(): static
    {
        return $this->state(fn (array $attributes) => [
            'interaction_type' => 'bookmark',
        ]);
    }
}
