<?php

namespace App\Domain\Planning;

use App\Enums\AcademicPeriodStatus;
use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\GroupTerm;
use Illuminate\Support\Facades\DB;

class AdvancePeriodAction
{
    public function __construct(
        private readonly GenerateSectionsForGroupTermAction $generateSections,
    ) {}

    public function execute(AdvancePeriodInput $input): AcademicPeriod
    {
        return DB::transaction(function () use ($input): AcademicPeriod {
            $sourcePeriod = AcademicPeriod::query()
                ->with([
                    'groupTerms.studentGroup.program.modality',
                    'groupTerms.studentGroup.curriculum.curriculumCourses',
                ])
                ->findOrFail($input->sourcePeriodId);

            $newPeriod = AcademicPeriod::query()->create([
                'code' => $input->code,
                'name' => $input->name,
                'year' => $input->year,
                'term_of_year' => $input->termOfYear,
                'starts_on' => $input->startsOn,
                'ends_on' => $input->endsOn,
                'weeks' => $input->weeks,
                'status' => AcademicPeriodStatus::Draft,
            ]);

            foreach ($sourcePeriod->groupTerms as $groupTerm) {
                $group = $groupTerm->studentGroup;

                if ($group->status !== StudentGroupStatus::Active) {
                    continue;
                }

                if (in_array($group->id, $input->excludedGroupIds, true)) {
                    continue;
                }

                if ($groupTerm->term_number >= 9) {
                    $group->update(['status' => StudentGroupStatus::Graduated]);

                    continue;
                }

                $studentCount = $input->studentCountOverrides[$group->id] ?? $groupTerm->student_count;

                $newGroupTerm = GroupTerm::query()->create([
                    'student_group_id' => $group->id,
                    'academic_period_id' => $newPeriod->id,
                    'term_number' => $groupTerm->term_number + 1,
                    'student_count' => max(0, $studentCount),
                    'student_count_confirmed' => false,
                ]);

                $this->generateSections->execute(
                    groupTerm: $newGroupTerm,
                    targetPeriod: $newPeriod,
                    suggestTeachers: $input->suggestTeachers,
                    sourcePeriod: $sourcePeriod,
                    copyMeetings: $input->copyMeetings,
                );
            }

            return $newPeriod;
        });
    }
}
