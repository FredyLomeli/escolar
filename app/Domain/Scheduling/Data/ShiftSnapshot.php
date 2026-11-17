<?php

namespace App\Domain\Scheduling\Data;

use App\Domain\Scheduling\TimeRange;

readonly class ShiftSnapshot
{
    /**
     * @param  array<int, list<TimeRange>>  $windowsByDay  Mapa day_of_week => ventanas fusionadas
     * @param  array<int, list<ReservedBlockSnapshot>>  $reservedBlocksByDay  Mapa day_of_week => bloques reservados
     */
    public function __construct(
        public int $id,
        public int $modalityId,
        public string $name,
        public array $windowsByDay = [],
        public array $reservedBlocksByDay = [],
    ) {}

    public function hasWindows(): bool
    {
        return $this->windowsByDay !== [];
    }
}
