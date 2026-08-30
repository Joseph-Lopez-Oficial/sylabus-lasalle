<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PerformanceLevel>
 */
class PerformanceLevelFactory extends Factory
{
    /**
     * Institutional grade for each of the four standard level orders.
     */
    private const INSTITUTIONAL_SCALE = [
        1 => 1.3,
        2 => 2.5,
        3 => 3.8,
        4 => 5.0,
    ];

    /**
     * Define the model's default state.
     *
     * The grade value follows the institutional scale when the order is one of
     * the four standard levels, so a level created as `['order' => 3]` carries
     * the grade the institution assigns to "Competente" without the caller
     * having to spell it out.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 10),
            'grade_value' => fn (array $attributes) => self::INSTITUTIONAL_SCALE[$attributes['order']] ?? null,
            'is_below_basic_threshold' => fn (array $attributes) => $attributes['order'] === 2,
        ];
    }

    /**
     * A level with no grade assigned, to exercise the null-value behaviour.
     */
    public function withoutGradeValue(): static
    {
        return $this->state(fn () => ['grade_value' => null]);
    }
}
