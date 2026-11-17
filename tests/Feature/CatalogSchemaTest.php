<?php

use App\Enums\AvailabilityType;
use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Enums\ModalityCode;
use App\Enums\TeacherCourseSource;
use App\Enums\TeacherOrigin;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\CurriculumTermRule;
use App\Models\Modality;
use App\Models\Program;
use App\Models\ReservedBlock;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftWindow;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherCourse;
use Illuminate\Database\QueryException;

test('unique constraints are enforced on catalog tables', function () {
    $modality = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'ADME', 'modality_id' => $modality->id]);

    expect(fn () => Program::factory()->create(['code' => 'ADME', 'modality_id' => $modality->id]))
        ->toThrow(QueryException::class);

    Curriculum::factory()->create(['program_id' => $program->id, 'version' => '2025']);
    expect(fn () => Curriculum::factory()->create(['program_id' => $program->id, 'version' => '2025']))
        ->toThrow(QueryException::class);

    Course::factory()->create(['subject_code' => 'LMFM', 'course_code' => 'HUS02']);
    expect(fn () => Course::factory()->create(['subject_code' => 'LMFM', 'course_code' => 'HUS02']))
        ->toThrow(QueryException::class);

    Teacher::factory()->create(['employee_id' => '00295221']);
    expect(fn () => Teacher::factory()->create(['employee_id' => '00295221']))
        ->toThrow(QueryException::class);

    Classroom::factory()->create(['code' => 'CTC 01']);
    expect(fn () => Classroom::factory()->create(['code' => 'CTC 01']))
        ->toThrow(QueryException::class);

    Setting::factory()->create(['key' => 'target_section_size']);
    expect(fn () => Setting::factory()->create(['key' => 'target_section_size']))
        ->toThrow(QueryException::class);
});

test('catalog relationships and enum casts work properly', function () {
    $modality = Modality::factory()->regular()->create();
    $program = Program::factory()->create(['code' => 'DISGD', 'modality_id' => $modality->id]);
    $curriculum = Curriculum::factory()->create(['program_id' => $program->id, 'version' => '2026']);
    $course = Course::factory()->create([
        'subject_code' => 'LMFM',
        'course_code' => 'HUS02',
        'name' => 'Razonamiento Crítico',
    ]);

    $curriculumCourse = CurriculumCourse::factory()->create([
        'curriculum_id' => $curriculum->id,
        'course_id' => $course->id,
        'term_number' => 1,
        'kind' => CourseKind::Additional,
        'default_delivery' => DeliveryType::OnlineConnect,
    ]);

    $rule = CurriculumTermRule::factory()->create([
        'curriculum_id' => $curriculum->id,
        'term_number' => 1,
        'expected_courses' => 7,
    ]);

    $teacher = Teacher::factory()->create(['origin' => TeacherOrigin::Campus]);
    TeacherAvailability::factory()->create([
        'teacher_id' => $teacher->id,
        'day_of_week' => 1,
        'type' => AvailabilityType::Available,
    ]);
    TeacherCourse::factory()->create([
        'teacher_id' => $teacher->id,
        'course_id' => $course->id,
        'source' => TeacherCourseSource::Manual,
    ]);

    $shift = Shift::factory()->create(['modality_id' => $modality->id, 'code' => 'matutino']);
    ShiftWindow::factory()->create(['shift_id' => $shift->id, 'day_of_week' => 1]);
    ReservedBlock::factory()->create(['shift_id' => $shift->id, 'name' => 'Inglés']);

    $curriculum->load(['program.modality', 'curriculumCourses.course', 'termRules']);
    $teacher->load(['availabilities', 'courses']);
    $shift->load(['modality', 'windows', 'reservedBlocks']);

    expect($modality->code)->toBe(ModalityCode::Regular)
        ->and($course->fullCode())->toBe('LMFM HUS02')
        ->and($curriculumCourse->kind)->toBe(CourseKind::Additional)
        ->and($curriculumCourse->default_delivery)->toBe(DeliveryType::OnlineConnect)
        ->and($curriculum->program->code)->toBe('DISGD')
        ->and($curriculum->curriculumCourses)->toHaveCount(1)
        ->and($curriculum->termRules->first()?->id)->toBe($rule->id)
        ->and($teacher->origin)->toBe(TeacherOrigin::Campus)
        ->and($teacher->availabilities->first()?->type)->toBe(AvailabilityType::Available)
        ->and($teacher->courses)->toHaveCount(1)
        ->and($shift->windows)->toHaveCount(1)
        ->and($shift->reservedBlocks)->toHaveCount(1);
});

test('settings helper stores and retrieves typed values with fallback', function () {
    expect(Setting::getValue('missing_key', 25))->toBe(25);

    Setting::setValue('target_section_size', 20, 'Tamaño objetivo por sección');

    expect(Setting::getValue('target_section_size'))->toBe(20);
});
