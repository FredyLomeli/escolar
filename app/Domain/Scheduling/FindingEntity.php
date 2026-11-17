<?php

namespace App\Domain\Scheduling;

readonly class FindingEntity
{
    public function __construct(
        public string $type,
        public int $id,
        public string $label,
    ) {}

    /**
     * @return array{type: string, id: int, label: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'label' => $this->label,
        ];
    }
}
