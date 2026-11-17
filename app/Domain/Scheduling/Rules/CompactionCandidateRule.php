<?php

namespace App\Domain\Scheduling\Rules;

use App\Domain\Scheduling\Data\ClassroomSnapshot;
use App\Domain\Scheduling\Data\MeetingSnapshot;
use App\Domain\Scheduling\Data\PeriodValidationContext;
use App\Domain\Scheduling\Data\SectionSnapshot;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingEntity;
use App\Domain\Scheduling\ScheduleOverlap;

final class CompactionCandidateRule implements PeriodValidationRule
{
    public function validate(PeriodValidationContext $context): array
    {
        if ($context->classrooms === []) {
            return [];
        }

        /** @var array<string, list<SectionSnapshot>> $buckets */
        $buckets = [];

        foreach ($context->sections as $section) {
            if ($section->meetings === []) {
                continue;
            }

            $modalityId = $this->resolveModalityId($context, $section);

            if ($modalityId === null) {
                continue;
            }

            $signature = $this->buildSignature($section, $modalityId);
            $buckets[$signature][] = $section;
        }

        $findings = [];
        $target = $context->settings->targetSectionSize;

        foreach ($buckets as $sections) {
            if (count($sections) < 2) {
                continue;
            }

            $totalStudents = array_sum(array_map(
                static fn (SectionSnapshot $section): int => $section->studentCount,
                $sections,
            ));

            $classroom = $this->findAvailableClassroom($context, $sections, $totalStudents);

            if ($classroom !== null) {
                $findings[] = $this->buildFinding($sections, $totalStudents, $target, $classroom);

                continue;
            }

            // Si todas juntas exceden la capacidad disponible, evaluar pares válidos priorizando cercanía al objetivo.
            $findings = array_merge($findings, $this->findValidPairs($context, $sections, $target));
        }

        return $findings;
    }

    private function resolveModalityId(PeriodValidationContext $context, SectionSnapshot $section): ?int
    {
        if ($section->groupTerms !== []) {
            return $section->groupTerms[0]->modalityId;
        }

        return $context->shiftsById[$section->shiftId]->modalityId ?? null;
    }

    private function buildSignature(SectionSnapshot $section, int $modalityId): string
    {
        $meetingParts = array_map(
            static fn (MeetingSnapshot $meeting): string => sprintf(
                '%d:%d-%d',
                $meeting->dayOfWeek,
                $meeting->timeRange->startMinutes,
                $meeting->timeRange->endMinutes,
            ),
            $section->meetings,
        );

        sort($meetingParts);

        return implode('|', [
            $section->courseId,
            $modalityId,
            $section->startWeek,
            $section->endWeek,
            implode(',', $meetingParts),
        ]);
    }

    /**
     * @param  list<SectionSnapshot>  $candidateSections
     */
    private function findAvailableClassroom(
        PeriodValidationContext $context,
        array $candidateSections,
        int $requiredCapacity,
    ): ?ClassroomSnapshot {
        $excludedSectionIds = array_flip(array_map(
            static fn (SectionSnapshot $section): int => $section->id,
            $candidateSections,
        ));

        $referenceMeetings = $candidateSections[0]->meetings;

        $eligibleClassrooms = array_values(array_filter(
            $context->classrooms,
            static fn (ClassroomSnapshot $classroom): bool => $classroom->active
                && $classroom->capacity >= $requiredCapacity,
        ));

        usort(
            $eligibleClassrooms,
            static fn (ClassroomSnapshot $a, ClassroomSnapshot $b): int => $a->capacity <=> $b->capacity,
        );

        foreach ($eligibleClassrooms as $classroom) {
            if ($this->isClassroomFree($context, $classroom->id, $referenceMeetings, $excludedSectionIds)) {
                return $classroom;
            }
        }

        return null;
    }

    /**
     * @param  list<MeetingSnapshot>  $referenceMeetings
     * @param  array<int, int>  $excludedSectionIds
     */
    private function isClassroomFree(
        PeriodValidationContext $context,
        int $classroomId,
        array $referenceMeetings,
        array $excludedSectionIds,
    ): bool {
        foreach ($context->sections as $otherSection) {
            if (isset($excludedSectionIds[$otherSection->id])) {
                continue;
            }

            foreach ($otherSection->meetings as $otherMeeting) {
                if ($otherMeeting->classroomId !== $classroomId) {
                    continue;
                }

                foreach ($referenceMeetings as $refMeeting) {
                    if (ScheduleOverlap::meetingsConflict($refMeeting, $otherMeeting)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param  list<SectionSnapshot>  $sections
     * @return list<Finding>
     */
    private function findValidPairs(PeriodValidationContext $context, array $sections, int $target): array
    {
        $candidates = [];
        $count = count($sections);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $pair = [$sections[$i], $sections[$j]];
                $totalStudents = $sections[$i]->studentCount + $sections[$j]->studentCount;
                $classroom = $this->findAvailableClassroom($context, $pair, $totalStudents);

                if ($classroom !== null) {
                    $candidates[] = [
                        'distance' => abs($totalStudents - $target),
                        'finding' => $this->buildFinding($pair, $totalStudents, $target, $classroom),
                    ];
                }
            }
        }

        usort(
            $candidates,
            static fn (array $a, array $b): int => $a['distance'] <=> $b['distance'],
        );

        return array_map(
            static fn (array $item): Finding => $item['finding'],
            $candidates,
        );
    }

    /**
     * @param  list<SectionSnapshot>  $sections
     */
    private function buildFinding(
        array $sections,
        int $totalStudents,
        int $target,
        ClassroomSnapshot $classroom,
    ): Finding {
        $sectionIds = array_map(
            static fn (SectionSnapshot $section): int => $section->id,
            $sections,
        );
        $first = $sections[0];
        $distance = abs($totalStudents - $target);

        return Finding::create(
            code: FindingCode::CompactionCandidate,
            message: sprintf(
                'Posible compactación para %s — %s (%d secciones): sumarían %d alumnos (objetivo ~%d, diferencia %d) con cupo en aula %s (cap. %d).',
                $first->courseCode,
                $first->courseName,
                count($sections),
                $totalStudents,
                $target,
                $distance,
                $classroom->code,
                $classroom->capacity,
            ),
            sectionIds: $sectionIds,
            entity: new FindingEntity('course', $first->courseId, "{$first->courseCode} — {$first->courseName}"),
        );
    }
}
