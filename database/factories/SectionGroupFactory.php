<?php

namespace Database\Factories;

use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\SectionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionGroup>
 */
class SectionGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'group_term_id' => GroupTerm::factory(),
        ];
    }
}
