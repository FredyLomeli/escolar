<?php

use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use App\Enums\StudentGroupStatus;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\SectionGroup;
use App\Models\SectionMeeting;
use App\Models\Shift;
use App\Models\StudentGroup;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;

test('unique constraints are enforced on period, group_terms, sections crn, and section_groups', function () {
    $period = AcademicPeriod::factory()->create(['code' => '202710']);

    expect(fn () => AcademicPeriod::factory()->create(['code' => '202710']))
        ->toThrow(QueryException::class);

    $group = StudentGroup::factory()->create(['entry_period_id' => $period->id]);
    $groupTerm = GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $period->id,
        'term_number' => 1,
    ]);

    expect(fn () => GroupTerm::factory()->create([
        'student_group_id' => $group->id,
        'academic_period_id' => $period->id,
        'term_number' => 2,
    ]))->toThrow(QueryException::class);

    // Múltiples secciones con crn NULL están permitidas en el mismo periodo
    $shift = Shift::factory()->create();
    $course = Course::factory()->create();
    $s1 = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $course->id,
        'shift_id' => $shift->id,
        'crn' => null,
    ]);
    Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $course->id,
        'shift_id' => $shift->id,
        'crn' => null,
    ]);

    // CRN duplicado en el mismo periodo rechazado
    Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $course->id,
        'shift_id' => $shift->id,
        'crn' => '55001',
    ]);

    expect(fn () => Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $course->id,
        'shift_id' => $shift->id,
        'crn' => '55001',
    ]))->toThrow(QueryException::class);

    SectionGroup::factory()->create([
        'section_id' => $s1->id,
        'group_term_id' => $groupTerm->id,
    ]);

    expect(fn () => SectionGroup::factory()->create([
        'section_id' => $s1->id,
        'group_term_id' => $groupTerm->id,
    ]))->toThrow(QueryException::class);
});

test('section studentCount and isCompacted handle single, compacted, and override counts', function () {
    $period = AcademicPeriod::factory()->create(['status' => AcademicPeriodStatus::Draft]);
    $shift = Shift::factory()->create();
    $course = Course::factory()->create();
    $classroom = Classroom::factory()->create(['capacity' => 35]);

    $gt1 = GroupTerm::factory()->create([
        'academic_period_id' => $period->id,
        'student_count' => 12,
    ]);
    $gt2 = GroupTerm::factory()->create([
        'academic_period_id' => $period->id,
        'student_count' => 11,
    ]);

    $section = Section::factory()->create([
        'academic_period_id' => $period->id,
        'course_id' => $course->id,
        'shift_id' => $shift->id,
        'delivery' => DeliveryType::InPerson,
        'status' => SectionStatus::Draft,
    ]);

    SectionGroup::factory()->create(['section_id' => $section->id, 'group_term_id' => $gt1->id]);
    SectionMeeting::factory()->create(['section_id' => $section->id, 'classroom_id' => $classroom->id]);

    $section->load(['groupTerms', 'meetings.classroom']);

    expect($section->studentCount())->toBe(12)
        ->and($section->isCompacted())->toBeFalse()
        ->and($section->meetings->first()?->classroom?->id)->toBe($classroom->id);

    // Compactar segundo grupo
    SectionGroup::factory()->create(['section_id' => $section->id, 'group_term_id' => $gt2->id]);
    $section->load('groupTerms');

    expect($section->studentCount())->toBe(23)
        ->and($section->isCompacted())->toBeTrue();

    // Override explícito
    $section->update(['students_override' => 19]);
    expect($section->studentCount())->toBe(19);
});

test('database seeder creates demo period with 3 active groups and sections idempotently', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $period = AcademicPeriod::query()->where('code', '202660')->first();

    expect($period)->not->toBeNull()
        ->and(AcademicPeriod::count())->toBe(1)
        ->and(StudentGroup::where('status', StudentGroupStatus::Active)->count())->toBe(3)
        ->and(GroupTerm::where('academic_period_id', $period?->id)->pluck('term_number')->sort()->values()->all())
        ->toBe([1, 6, 9])
        ->and(Section::where('academic_period_id', $period?->id)->count())->toBeGreaterThanOrEqual(7);
});
