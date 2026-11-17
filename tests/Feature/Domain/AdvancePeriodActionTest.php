<?php

use App\Domain\Planning\AdvancePeriodAction;
use App\Domain\Planning\AdvancePeriodInput;
use App\Domain\Planning\GenerateSectionsForGroupTermAction;
use App\Domain\Planning\PreviewPeriodAdvancementAction;
use App\Enums\AcademicPeriodStatus;
use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\GroupTerm;
use App\Models\Modality;
use App\Models\Program;
use App\Models\Section;
use App\Models\SectionGroup;
use App\Models\SectionMeeting;
use App\Models\Shift;
use App\Models\StudentGroup;
use App\Models\Teacher;
use Database\Seeders\SettingsSeeder;

test('preview calculates advancing and graduating groups without mutating database', function () {
    $this->seed(SettingsSeeder::class);

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'ADME', 'modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);

    $c1 = Course::factory()->create();
    $c2 = Course::factory()->create();
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $c1->id, 'term_number' => 2]);
    CurriculumCourse::factory()->create(['curriculum_id' => $curriculum->id, 'course_id' => $c2->id, 'term_number' => 2]);

    $sourcePeriod = AcademicPeriod::factory()->create(['code' => '202630', 'status' => AcademicPeriodStatus::Published]);

    $g1 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $sourcePeriod->id,
    ]);
    $g9 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $sourcePeriod->id,
    ]);

    GroupTerm::factory()->create([
        'student_group_id' => $g1->id,
        'academic_period_id' => $sourcePeriod->id,
        'term_number' => 1,
        'student_count' => 21,
    ]);
    GroupTerm::factory()->create([
        'student_group_id' => $g9->id,
        'academic_period_id' => $sourcePeriod->id,
        'term_number' => 9,
        'student_count' => 15,
    ]);

    $preview = app(PreviewPeriodAdvancementAction::class)->execute($sourcePeriod);

    expect($preview['advancing'])->toHaveCount(1)
        ->and($preview['advancing'][0]['next_term'])->toBe(2)
        ->and($preview['advancing'][0]['estimated_sections'])->toBe(2)
        ->and($preview['graduating'])->toHaveCount(1)
        ->and($preview['graduating'][0]['student_group_id'])->toBe($g9->id)
        ->and(AcademicPeriod::count())->toBe(1)
        ->and($g9->fresh()?->status)->toBe(StudentGroupStatus::Active);
});

test('advance period promotes active groups, graduates 9th term, generates plan sections, and suggests historical teachers', function () {
    $this->seed(SettingsSeeder::class);

    $regular = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'ADME', 'modality_id' => $regular->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $regular->id]);

    $courseTerm2InPerson = Course::factory()->create(['subject_code' => 'ADME', 'course_code' => 'MKT21']);
    $courseTerm2Async = Course::factory()->create(['subject_code' => 'TRCO', 'course_code' => 'MET22']);
    $courseTerm7Stay = Course::factory()->create(['subject_code' => 'ESTD', 'course_code' => 'EST70']);

    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculum->id,
        'course_id' => $courseTerm2InPerson->id,
        'term_number' => 2,
        'default_delivery' => DeliveryType::InPerson,
    ]);
    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculum->id,
        'course_id' => $courseTerm2Async->id,
        'term_number' => 2,
        'default_delivery' => DeliveryType::OnlineAsync,
    ]);
    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculum->id,
        'course_id' => $courseTerm7Stay->id,
        'term_number' => 7,
        'kind' => CourseKind::Stay,
        'default_delivery' => DeliveryType::InPerson,
    ]);

    $sourcePeriod = AcademicPeriod::factory()->create([
        'code' => '202630',
        'year' => 2026,
        'term_of_year' => 3,
        'weeks' => 14,
        'status' => AcademicPeriodStatus::Published,
    ]);

    // Docente histórico activo para MKT21 y para MET22 (MET22 no debe asignarse por ser @)
    $teacher = Teacher::factory()->create(['active' => true]);
    Section::factory()->create([
        'academic_period_id' => $sourcePeriod->id,
        'course_id' => $courseTerm2InPerson->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
    ]);
    Section::factory()->create([
        'academic_period_id' => $sourcePeriod->id,
        'course_id' => $courseTerm2Async->id,
        'teacher_id' => $teacher->id,
        'shift_id' => $shift->id,
    ]);

    $g1 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $sourcePeriod->id,
        'label' => 'ADME-1',
    ]);
    $g6 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $sourcePeriod->id,
        'label' => 'ADME-6',
    ]);
    $g9 = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $sourcePeriod->id,
        'label' => 'ADME-9',
    ]);

    GroupTerm::factory()->create(['student_group_id' => $g1->id, 'academic_period_id' => $sourcePeriod->id, 'term_number' => 1, 'student_count' => 20]);
    GroupTerm::factory()->create(['student_group_id' => $g6->id, 'academic_period_id' => $sourcePeriod->id, 'term_number' => 6, 'student_count' => 18]);
    GroupTerm::factory()->create(['student_group_id' => $g9->id, 'academic_period_id' => $sourcePeriod->id, 'term_number' => 9, 'student_count' => 15]);

    $newPeriod = app(AdvancePeriodAction::class)->execute(new AdvancePeriodInput(
        code: '202710',
        name: 'Ene – Abr 2027',
        year: 2027,
        termOfYear: 1,
        startsOn: '2027-01-11',
        endsOn: '2027-04-23',
        weeks: 14,
        sourcePeriodId: $sourcePeriod->id,
        copyMeetings: false,
        suggestTeachers: true,
        studentCountOverrides: [$g1->id => 23],
    ));

    expect($newPeriod->status)->toBe(AcademicPeriodStatus::Draft)
        ->and($g9->fresh()?->status)->toBe(StudentGroupStatus::Graduated)
        ->and(GroupTerm::where('academic_period_id', $newPeriod->id)->count())->toBe(2);

    $newTermG1 = GroupTerm::where('academic_period_id', $newPeriod->id)->where('student_group_id', $g1->id)->firstOrFail();
    $newTermG6 = GroupTerm::where('academic_period_id', $newPeriod->id)->where('student_group_id', $g6->id)->firstOrFail();

    expect($newTermG1->term_number)->toBe(2)
        ->and($newTermG1->student_count)->toBe(23)
        ->and($newTermG1->student_count_confirmed)->toBeFalse()
        ->and($newTermG6->term_number)->toBe(7);

    $sectionInPerson = Section::where('academic_period_id', $newPeriod->id)
        ->where('course_id', $courseTerm2InPerson->id)
        ->firstOrFail();
    $sectionAsync = Section::where('academic_period_id', $newPeriod->id)
        ->where('course_id', $courseTerm2Async->id)
        ->firstOrFail();
    $sectionStay = Section::where('academic_period_id', $newPeriod->id)
        ->where('course_id', $courseTerm7Stay->id)
        ->firstOrFail();

    expect($sectionInPerson->teacher_id)->toBe($teacher->id)
        ->and($sectionInPerson->start_week)->toBe(1)
        ->and($sectionInPerson->end_week)->toBe(14)
        ->and($sectionAsync->teacher_id)->toBeNull()
        ->and($sectionStay->groupTerms()->pluck('group_terms.id')->all())->toBe([$newTermG6->id]);
});

test('copy meetings duplicates prior meetings for same group and course when enabled, and executive defaults to 5 weeks', function () {
    $this->seed(SettingsSeeder::class);

    $executive = Modality::factory()->executive()->create();
    $program = Program::factory()->create(['code' => 'EJAD', 'modality_id' => $executive->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id]);
    $shift = Shift::factory()->create(['modality_id' => $executive->id]);
    $classroom = Classroom::factory()->create();
    $course = Course::factory()->create();

    CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculum->id,
        'course_id' => $course->id,
        'term_number' => 2,
        'default_delivery' => DeliveryType::InPerson,
    ]);

    $sourcePeriod = AcademicPeriod::factory()->create(['code' => '202630', 'weeks' => 15, 'status' => AcademicPeriodStatus::Published]);
    $group = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $sourcePeriod->id,
    ]);
    $sourceTerm = GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $sourcePeriod->id,
        'term_number' => 1,
    ]);

    $sourceSection = Section::factory()->create([
        'academic_period_id' => $sourcePeriod->id,
        'course_id' => $course->id,
        'shift_id' => $shift->id,
    ]);
    SectionGroup::factory()->create(['section_id' => $sourceSection->id, 'group_term_id' => $sourceTerm->id]);
    SectionMeeting::factory()->create([
        'section_id' => $sourceSection->id,
        'day_of_week' => 6,
        'starts_at' => '08:00:00',
        'ends_at' => '11:00:00',
        'classroom_id' => $classroom->id,
    ]);

    $newPeriod = app(AdvancePeriodAction::class)->execute(new AdvancePeriodInput(
        code: '202710',
        name: 'Ene – Abr 2027',
        year: 2027,
        termOfYear: 1,
        startsOn: '2027-01-11',
        endsOn: '2027-04-23',
        weeks: 15,
        sourcePeriodId: $sourcePeriod->id,
        copyMeetings: true,
        suggestTeachers: false,
    ));

    $generatedSection = Section::with('meetings')
        ->where('academic_period_id', $newPeriod->id)
        ->firstOrFail();

    expect($generatedSection->start_week)->toBe(1)
        ->and($generatedSection->end_week)->toBe(5)
        ->and($generatedSection->meetings)->toHaveCount(1)
        ->and($generatedSection->meetings->first()?->day_of_week)->toBe(6)
        ->and($generatedSection->meetings->first()?->classroom_id)->toBe($classroom->id);

    // Probar también GenerateSectionsForGroupTermAction directo para grupo nuevo en 2.º sin copiar sesiones
    $otherGroup = StudentGroup::factory()->create([
        'program_id' => $program->id,
        'curriculum_id' => $curriculum->id,
        'shift_id' => $shift->id,
        'entry_period_id' => $newPeriod->id,
    ]);
    $otherTerm = GroupTerm::factory()->create([
        'student_group_id' => $otherGroup->id,
        'academic_period_id' => $newPeriod->id,
        'term_number' => 2,
    ]);

    $standalone = app(GenerateSectionsForGroupTermAction::class)->execute(
        groupTerm: $otherTerm,
        targetPeriod: $newPeriod,
        suggestTeachers: false,
        sourcePeriod: $sourcePeriod,
        copyMeetings: false,
    );

    expect($standalone)->toHaveCount(1)
        ->and($standalone->first()?->meetings()->count())->toBe(0);
});
