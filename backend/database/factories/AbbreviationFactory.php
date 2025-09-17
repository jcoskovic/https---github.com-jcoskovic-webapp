<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Abbreviation>
 */
class AbbreviationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $abbreviations = ['API', 'JSON', 'HTTP', 'SQL', 'CSS', 'HTML', 'JS', 'PHP', 'URL', 'XML'];
        $meanings = [
            'API' => 'Application Programming Interface',
            'JSON' => 'JavaScript Object Notation',
            'HTTP' => 'HyperText Transfer Protocol',
            'SQL' => 'Structured Query Language',
            'CSS' => 'Cascading Style Sheets',
            'HTML' => 'HyperText Markup Language',
            'JS' => 'JavaScript',
            'PHP' => 'PHP: Hypertext Preprocessor',
            'URL' => 'Uniform Resource Locator',
            'XML' => 'eXtensible Markup Language',
        ];

        $abbr = fake()->randomElement($abbreviations);

        return [
            'abbreviation' => $abbr,
            'meaning' => $meanings[$abbr] ?? fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->randomElement(['Tehnologija', 'Poslovanje', 'Razvoj', 'Komunikacija']),
            'department' => fake()->optional()->company(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the abbreviation should be approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the abbreviation should be pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
