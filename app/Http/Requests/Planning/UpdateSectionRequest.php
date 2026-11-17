<?php

namespace App\Http\Requests\Planning;

use App\Models\Section;

class UpdateSectionRequest extends SectionFormRequest
{
    private function routeSection(): ?Section
    {
        $section = $this->route('section');

        return $section instanceof Section ? $section : null;
    }

    public function authorize(): bool
    {
        $section = $this->routeSection();

        return $section !== null && ($this->user()?->can('update', $section) ?? false);
    }

    protected function targetPeriodId(): int
    {
        $section = $this->routeSection();

        return $section !== null ? $section->academic_period_id : 0;
    }

    protected function ignoredSectionId(): ?int
    {
        return $this->routeSection()?->id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->baseSectionRules();
    }
}
