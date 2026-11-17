<?php

namespace App\Domain\Scheduling;

readonly class Finding
{
    /**
     * @param  list<int>  $sectionIds
     * @param  array<string, mixed>|null  $suggestion
     */
    public function __construct(
        public FindingCode $code,
        public FindingSeverity $severity,
        public string $message,
        public array $sectionIds = [],
        public ?FindingEntity $entity = null,
        public ?array $suggestion = null,
    ) {}

    /**
     * @param  list<int>  $sectionIds
     * @param  array<string, mixed>|null  $suggestion
     */
    public static function create(
        FindingCode $code,
        string $message,
        array $sectionIds = [],
        ?FindingEntity $entity = null,
        ?array $suggestion = null,
    ): self {
        return new self(
            code: $code,
            severity: $code->defaultSeverity(),
            message: $message,
            sectionIds: array_values(array_unique($sectionIds)),
            entity: $entity,
            suggestion: $suggestion,
        );
    }

    /**
     * @return array{
     *     code: string,
     *     severity: string,
     *     message: string,
     *     section_ids: list<int>,
     *     entity: array{type: string, id: int, label: string}|null,
     *     suggestion: array<string, mixed>|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'severity' => $this->severity->value,
            'message' => $this->message,
            'section_ids' => $this->sectionIds,
            'entity' => $this->entity?->toArray(),
            'suggestion' => $this->suggestion,
        ];
    }
}
