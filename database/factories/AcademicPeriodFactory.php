<?php

namespace Database\Factories;

use App\Enums\AcademicPeriodStatus;
use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2025, 2035);
        $term = fake()->numberBetween(1, 3);
        $code = sprintf('%d%d0', $year, $term);

        return [
            'code' => fake()->unique()->numerify('202###'),
            'name' => "Periodo {$code}",
            'year' => $year,
            'term_of_year' => $term,
            'starts_on' => "{$year}-01-05",
            'ends_on' => "{$year}-04-25",
            'weeks' => 14,
            'status' => AcademicPeriodStatus::Draft,
        ];
    }
}
