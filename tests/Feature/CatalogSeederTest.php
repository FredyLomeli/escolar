<?php

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Modality;
use App\Models\Program;
use App\Models\ReservedBlock;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\ShiftWindow;
use App\Models\Teacher;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SettingsSeeder;

test('database seeder populates initial settings and catalogs idempotently', function () {
    $this->seed(DatabaseSeeder::class);

    $countsAfterFirst = [
        'modalities' => Modality::count(),
        'shifts' => Shift::count(),
        'windows' => ShiftWindow::count(),
        'blocks' => ReservedBlock::count(),
        'classrooms' => Classroom::count(),
        'programs' => Program::count(),
        'curricula' => Curriculum::count(),
        'courses' => Course::count(),
        'teachers' => Teacher::count(),
        'settings' => Setting::count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect($countsAfterFirst['modalities'])->toBe(2)
        ->and($countsAfterFirst['shifts'])->toBe(5)
        ->and($countsAfterFirst['windows'])->toBeGreaterThanOrEqual(21)
        ->and($countsAfterFirst['blocks'])->toBe(8)
        ->and($countsAfterFirst['classrooms'])->toBe(8)
        ->and($countsAfterFirst['programs'])->toBe(2)
        ->and($countsAfterFirst['curricula'])->toBe(2)
        ->and($countsAfterFirst['courses'])->toBe(15)
        ->and($countsAfterFirst['teachers'])->toBe(10)
        ->and($countsAfterFirst['settings'])->toBe(9)
        ->and(Modality::count())->toBe($countsAfterFirst['modalities'])
        ->and(Shift::count())->toBe($countsAfterFirst['shifts'])
        ->and(ShiftWindow::count())->toBe($countsAfterFirst['windows'])
        ->and(ReservedBlock::count())->toBe($countsAfterFirst['blocks'])
        ->and(Classroom::count())->toBe($countsAfterFirst['classrooms'])
        ->and(Program::count())->toBe($countsAfterFirst['programs'])
        ->and(Curriculum::count())->toBe($countsAfterFirst['curricula'])
        ->and(Course::count())->toBe($countsAfterFirst['courses'])
        ->and(Teacher::count())->toBe($countsAfterFirst['teachers'])
        ->and(Setting::count())->toBe($countsAfterFirst['settings'])
        ->and(Setting::getValue('target_section_size'))->toBe(20)
        ->and(Setting::getValue('weeks_in_term_regular'))->toBe(14)
        ->and(Setting::getValue('excel_teacher_markers'))->toBe([
            '@' => 'online_async',
            'VV' => 'online_live',
            'CC' => 'online_connect',
        ]);
});

test('settings seeder does not overwrite customized values', function () {
    $this->seed(SettingsSeeder::class);

    Setting::setValue('target_section_size', 24);

    $this->seed(SettingsSeeder::class);

    expect(Setting::getValue('target_section_size'))->toBe(24);
});
