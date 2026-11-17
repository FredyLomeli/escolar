<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Finding;

interface PeriodValidationRule
{
    /**
     * @return list<Finding>
     */
    public function validate(PeriodValidationContext $context): array;
}
