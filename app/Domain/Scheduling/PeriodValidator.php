<?php

namespace App\Domain\Scheduling;

use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Rules\CapacityExceededRule;
use App\Domain\Scheduling\Rules\ClassroomConflictRule;
use App\Domain\Scheduling\Rules\CompactionCandidateRule;
use App\Domain\Scheduling\Rules\CompactionValidityRule;
use App\Domain\Scheduling\Rules\DuplicateCrnRule;
use App\Domain\Scheduling\Rules\GroupConflictRule;
use App\Domain\Scheduling\Rules\GroupCourseCountRule;
use App\Domain\Scheduling\Rules\HoursMismatchRule;
use App\Domain\Scheduling\Rules\PeriodValidationRule;
use App\Domain\Scheduling\Rules\ReservedBlockConflictRule;
use App\Domain\Scheduling\Rules\SectionScheduleRule;
use App\Domain\Scheduling\Rules\SectionSizeRule;
use App\Domain\Scheduling\Rules\SectionTeacherPresenceRule;
use App\Domain\Scheduling\Rules\ShiftWindowRule;
use App\Domain\Scheduling\Rules\TeacherAvailabilityRule;
use App\Domain\Scheduling\Rules\TeacherConflictRule;
use App\Domain\Scheduling\Rules\TeacherEligibilityRule;
use App\Domain\Scheduling\Rules\TeacherOverloadRule;
use App\Models\AcademicPeriod;

class PeriodValidator
{
    /** @var list<PeriodValidationRule> */
    private array $rules;

    /**
     * @param  list<PeriodValidationRule>|null  $rules
     */
    public function __construct(
        private readonly PeriodValidationContextFactory $contextFactory = new PeriodValidationContextFactory,
        ?array $rules = null,
    ) {
        $this->rules = $rules ?? self::defaultRules();
    }

    public function validate(AcademicPeriod $period): ValidationReport
    {
        $context = $this->contextFactory->build($period);

        return $this->validateContext($context);
    }

    public function validateContext(PeriodValidationContext $context): ValidationReport
    {
        $findings = [];

        foreach ($this->rules as $rule) {
            foreach ($rule->validate($context) as $finding) {
                $findings[] = $finding;
            }
        }

        usort($findings, static function (Finding $a, Finding $b): int {
            $order = [
                FindingSeverity::Error->value => 0,
                FindingSeverity::Warning->value => 1,
                FindingSeverity::Info->value => 2,
            ];

            return ($order[$a->severity->value] <=> $order[$b->severity->value])
                ?: ($a->code->value <=> $b->code->value);
        });

        return new ValidationReport(
            academicPeriodId: $context->period->id,
            findings: $findings,
        );
    }

    /**
     * @return list<PeriodValidationRule>
     */
    public static function defaultRules(): array
    {
        return [
            new TeacherConflictRule,
            new ClassroomConflictRule,
            new GroupConflictRule,
            new CapacityExceededRule,
            new ReservedBlockConflictRule,
            new TeacherAvailabilityRule,
            new DuplicateCrnRule,
            new CompactionValidityRule,

            new ShiftWindowRule,
            new TeacherEligibilityRule,
            new TeacherOverloadRule,
            new SectionSizeRule,
            new GroupCourseCountRule,
            new SectionTeacherPresenceRule,
            new SectionScheduleRule,
            new HoursMismatchRule,

            new CompactionCandidateRule,
        ];
    }
}
