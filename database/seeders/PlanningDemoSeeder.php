<?php

namespace Database\Seeders;

use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Curriculum;
use App\Models\GroupTerm;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionGroup;
use App\Models\SectionMeeting;
use App\Models\Shift;
use App\Models\StudentGroup;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class PlanningDemoSeeder extends Seeder
{
    public function run(): void
    {
        $period = AcademicPeriod::query()->firstOrCreate(
            ['code' => '202660'],
            [
                'name' => 'Ago – Dic 2026',
                'year' => 2026,
                'term_of_year' => 3,
                'starts_on' => '2026-08-24',
                'ends_on' => '2026-12-11',
                'weeks' => 15,
                'status' => AcademicPeriodStatus::Published,
            ],
        );

        $adme = Program::query()->where('code', 'ADME')->first();
        $ejad = Program::query()->where('code', 'EJAD')->first();
        $matutino = Shift::query()->where('code', 'matutino')->first();
        $vespertino = Shift::query()->where('code', 'vespertino')->first();
        $sabado = Shift::query()->where('code', 'ejecutivo-sabado')->first();

        if (! $adme || ! $ejad || ! $matutino || ! $vespertino || ! $sabado) {
            return;
        }

        $admePlan = Curriculum::query()->where('program_id', $adme->id)->first();
        $ejadPlan = Curriculum::query()->where('program_id', $ejad->id)->first();

        if (! $admePlan || ! $ejadPlan) {
            return;
        }

        $admePlan->load('curriculumCourses');
        $ejadPlan->load('curriculumCourses');

        $classroom101 = Classroom::query()->where('code', '101')->first();
        $classroom201 = Classroom::query()->where('code', '201')->first();
        $teacher1 = Teacher::query()->where('employee_id', '00100001')->first();
        $teacher2 = Teacher::query()->where('employee_id', '00100002')->first();
        $teacher8 = Teacher::query()->where('employee_id', '00100008')->first();

        // Grupo 1: ADME Matutino en 1.º cuatrimestre
        $group1 = StudentGroup::query()->firstOrCreate(
            [
                'program_id' => $adme->id,
                'curriculum_id' => $admePlan->id,
                'shift_id' => $matutino->id,
                'entry_period_id' => $period->id,
                'label' => 'ADME-2026-M',
            ],
            ['status' => StudentGroupStatus::Active],
        );

        $term1 = GroupTerm::query()->firstOrCreate(
            [
                'student_group_id' => $group1->id,
                'academic_period_id' => $period->id,
            ],
            [
                'term_number' => 1,
                'student_count' => 22,
                'student_count_confirmed' => true,
            ],
        );

        // Grupo 2: ADME Vespertino en 6.º cuatrimestre (próximo pasa a 7.º estadía)
        $group2 = StudentGroup::query()->firstOrCreate(
            [
                'program_id' => $adme->id,
                'curriculum_id' => $admePlan->id,
                'shift_id' => $vespertino->id,
                'entry_period_id' => $period->id,
                'label' => 'ADME-2025-V',
            ],
            ['status' => StudentGroupStatus::Active],
        );

        $term2 = GroupTerm::query()->firstOrCreate(
            [
                'student_group_id' => $group2->id,
                'academic_period_id' => $period->id,
            ],
            [
                'term_number' => 6,
                'student_count' => 18,
                'student_count_confirmed' => true,
            ],
        );

        // Grupo 3: EJAD Sabatino en 9.º cuatrimestre (próximo queda graduado)
        $group3 = StudentGroup::query()->firstOrCreate(
            [
                'program_id' => $ejad->id,
                'curriculum_id' => $ejadPlan->id,
                'shift_id' => $sabado->id,
                'entry_period_id' => $period->id,
                'label' => 'EJAD-2024-S',
            ],
            ['status' => StudentGroupStatus::Active],
        );

        $term3 = GroupTerm::query()->firstOrCreate(
            [
                'student_group_id' => $group3->id,
                'academic_period_id' => $period->id,
            ],
            [
                'term_number' => 9,
                'student_count' => 16,
                'student_count_confirmed' => true,
            ],
        );

        // Secciones de ejemplo para Grupo 1 (1.º ADME)
        if ($term1->sectionGroups()->count() === 0) {
            $firstTermCourses = $admePlan->curriculumCourses->where('term_number', 1)->values();
            foreach ($firstTermCourses as $index => $cc) {
                $crn = (string) (10101 + $index);
                $section = Section::query()->firstOrCreate(
                    ['academic_period_id' => $period->id, 'crn' => $crn],
                    [
                        'course_id' => $cc->course_id,
                        'teacher_id' => $cc->default_delivery === DeliveryType::InPerson
                            ? ($index % 2 === 0 ? $teacher1?->id : $teacher2?->id)
                            : null,
                        'delivery' => $cc->default_delivery,
                        'shift_id' => $matutino->id,
                        'start_week' => 1,
                        'end_week' => 14,
                        'status' => SectionStatus::Confirmed,
                    ],
                );

                SectionGroup::query()->firstOrCreate([
                    'section_id' => $section->id,
                    'group_term_id' => $term1->id,
                ]);

                if ($index < 2) {
                    $startHour = sprintf('%02d:00:00', 8 + ($index * 2));
                    $endHour = sprintf('%02d:00:00', 10 + ($index * 2));
                    SectionMeeting::query()->firstOrCreate([
                        'section_id' => $section->id,
                        'day_of_week' => $index === 0 ? 1 : 2,
                        'starts_at' => $startHour,
                        'ends_at' => $endHour,
                    ], [
                        'classroom_id' => $classroom101?->id,
                    ]);
                }
            }
        }

        // Sección de ejemplo para Grupo 2 (6.º ADME)
        if ($term2->sectionGroups()->count() === 0) {
            $sixthCourse = $admePlan->curriculumCourses->where('term_number', 6)->first();
            if ($sixthCourse) {
                $section = Section::query()->firstOrCreate(
                    ['academic_period_id' => $period->id, 'crn' => '10201'],
                    [
                        'course_id' => $sixthCourse->course_id,
                        'teacher_id' => $teacher1?->id,
                        'delivery' => $sixthCourse->default_delivery,
                        'shift_id' => $vespertino->id,
                        'start_week' => 1,
                        'end_week' => 14,
                        'status' => SectionStatus::Confirmed,
                    ],
                );

                SectionGroup::query()->firstOrCreate([
                    'section_id' => $section->id,
                    'group_term_id' => $term2->id,
                ]);

                SectionMeeting::query()->firstOrCreate([
                    'section_id' => $section->id,
                    'day_of_week' => 3,
                    'starts_at' => '16:00:00',
                    'ends_at' => '18:00:00',
                ], [
                    'classroom_id' => $classroom201?->id,
                ]);
            }
        }

        // Sección de ejemplo para Grupo 3 (9.º EJAD)
        if ($term3->sectionGroups()->count() === 0) {
            $sampleCourse = $ejadPlan->curriculumCourses->first();
            if ($sampleCourse) {
                $section = Section::query()->firstOrCreate(
                    ['academic_period_id' => $period->id, 'crn' => '10301'],
                    [
                        'course_id' => $sampleCourse->course_id,
                        'teacher_id' => $teacher8?->id,
                        'delivery' => DeliveryType::InPerson,
                        'shift_id' => $sabado->id,
                        'start_week' => 1,
                        'end_week' => 5,
                        'status' => SectionStatus::Confirmed,
                    ],
                );

                SectionGroup::query()->firstOrCreate([
                    'section_id' => $section->id,
                    'group_term_id' => $term3->id,
                ]);

                SectionMeeting::query()->firstOrCreate([
                    'section_id' => $section->id,
                    'day_of_week' => 6,
                    'starts_at' => '08:00:00',
                    'ends_at' => '11:00:00',
                ], [
                    'classroom_id' => $classroom201?->id,
                ]);
            }
        }
    }
}
