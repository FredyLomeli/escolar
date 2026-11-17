<?php

namespace App\Domain\Scheduling\Data;

use App\Enums\AcademicPeriodStatus;

readonly class PeriodSnapshot
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $weeks,
        public AcademicPeriodStatus $status,
    ) {}
}
