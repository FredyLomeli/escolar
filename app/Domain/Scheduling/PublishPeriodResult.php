<?php

namespace App\Domain\Scheduling;

use App\Enums\AcademicPeriodStatus;

readonly class PublishPeriodResult
{
    public function __construct(
        public bool $published,
        public string $message,
        public ValidationReport $report,
        public AcademicPeriodStatus $previousStatus,
        public AcademicPeriodStatus $currentStatus,
    ) {}
}
