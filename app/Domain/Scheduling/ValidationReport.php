<?php

namespace App\Domain\Scheduling;

readonly class ValidationReport
{
    /**
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public int $academicPeriodId,
        public array $findings,
    ) {}

    /**
     * @return list<Finding>
     */
    public function errors(): array
    {
        return $this->bySeverity(FindingSeverity::Error);
    }

    /**
     * @return list<Finding>
     */
    public function warnings(): array
    {
        return $this->bySeverity(FindingSeverity::Warning);
    }

    /**
     * @return list<Finding>
     */
    public function infos(): array
    {
        return $this->bySeverity(FindingSeverity::Info);
    }

    /**
     * @return array{errors: int, warnings: int, infos: int}
     */
    public function counts(): array
    {
        $errors = 0;
        $warnings = 0;
        $infos = 0;

        foreach ($this->findings as $finding) {
            match ($finding->severity) {
                FindingSeverity::Error => $errors++,
                FindingSeverity::Warning => $warnings++,
                FindingSeverity::Info => $infos++,
            };
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'infos' => $infos,
        ];
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }

    public function canPublish(bool $allowPublishWithWarnings = true): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        return $allowPublishWithWarnings || $this->warnings() === [];
    }

    public function forSection(int $sectionId): self
    {
        return $this->forSections([$sectionId]);
    }

    /**
     * @param  list<int>  $sectionIds
     */
    public function forSections(array $sectionIds): self
    {
        if ($sectionIds === []) {
            return new self($this->academicPeriodId, []);
        }

        $lookup = array_flip($sectionIds);

        $filtered = array_values(array_filter(
            $this->findings,
            static function (Finding $finding) use ($lookup): bool {
                foreach ($finding->sectionIds as $id) {
                    if (isset($lookup[$id])) {
                        return true;
                    }
                }

                return false;
            },
        ));

        return new self($this->academicPeriodId, $filtered);
    }

    /**
     * @return array{
     *     academic_period_id: int,
     *     counts: array{errors: int, warnings: int, infos: int},
     *     can_publish: bool,
     *     findings: list<array{
     *         code: string,
     *         severity: string,
     *         message: string,
     *         section_ids: list<int>,
     *         entity: array{type: string, id: int, label: string}|null,
     *         suggestion: array<string, mixed>|null,
     *     }>,
     * }
     */
    public function toArray(bool $allowPublishWithWarnings = true): array
    {
        return [
            'academic_period_id' => $this->academicPeriodId,
            'counts' => $this->counts(),
            'can_publish' => $this->canPublish($allowPublishWithWarnings),
            'findings' => array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $this->findings,
            ),
        ];
    }

    /**
     * @return list<Finding>
     */
    private function bySeverity(FindingSeverity $severity): array
    {
        return array_values(array_filter(
            $this->findings,
            static fn (Finding $finding): bool => $finding->severity === $severity,
        ));
    }
}
