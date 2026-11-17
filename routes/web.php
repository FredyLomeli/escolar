<?php

use App\Http\Controllers\Catalogs\ClassroomController;
use App\Http\Controllers\Catalogs\CourseController;
use App\Http\Controllers\Catalogs\CurriculumController;
use App\Http\Controllers\Catalogs\CurriculumCourseController;
use App\Http\Controllers\Catalogs\ProgramController;
use App\Http\Controllers\Catalogs\ReservedBlockController;
use App\Http\Controllers\Catalogs\SettingController;
use App\Http\Controllers\Catalogs\ShiftController;
use App\Http\Controllers\Catalogs\ShiftWindowController;
use App\Http\Controllers\Catalogs\TeacherAvailabilityController;
use App\Http\Controllers\Catalogs\TeacherController;
use App\Http\Controllers\Catalogs\TeacherCourseController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/planning', fn () => Inertia::render('SectionPlaceholder', [
        'title' => 'Tablero de planeación',
        'subtitle' => 'Horarios por grupo, docente y aula con validación de choques en vivo.',
        'phase' => 'Fase 4',
        'description' => 'Aquí podrás visualizar la cuadrícula semanal, asignar docentes y aulas, revisar alertas y gestionar compactaciones.',
    ]))->name('planning.index');

    Route::get('/groups', fn () => Inertia::render('SectionPlaceholder', [
        'title' => 'Grupos del periodo',
        'subtitle' => 'Generaciones activas, cuatrimestres y confirmación de alumnos.',
        'phase' => 'Fase 2',
        'description' => 'Aquí consultarás los grupos por periodo, editarás la matrícula estimada y confirmarás el número de alumnos.',
    ]))->name('groups.index');

    Route::prefix('catalogs')->name('catalogs.')->group(function (): void {
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
        Route::patch('/programs/{program}/toggle-active', [ProgramController::class, 'toggleActive'])->name('programs.toggle-active');

        Route::post('/programs/{program}/curricula', [CurriculumController::class, 'store'])->name('programs.curricula.store');
        Route::get('/curricula/{curriculum}', [CurriculumController::class, 'show'])->name('curricula.show');
        Route::put('/curricula/{curriculum}', [CurriculumController::class, 'update'])->name('curricula.update');
        Route::patch('/curricula/{curriculum}/toggle-active', [CurriculumController::class, 'toggleActive'])->name('curricula.toggle-active');
        Route::put('/curricula/{curriculum}/term-rules/{termNumber}', [CurriculumController::class, 'updateTermRule'])->name('curricula.term-rules.update');
        Route::post('/curricula/{curriculum}/courses', [CurriculumCourseController::class, 'store'])->name('curricula.courses.store');
        Route::put('/curriculum-courses/{curriculumCourse}', [CurriculumCourseController::class, 'update'])->name('curriculum-courses.update');
        Route::delete('/curriculum-courses/{curriculumCourse}', [CurriculumCourseController::class, 'destroy'])->name('curriculum-courses.destroy');

        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/check-pair', [CourseController::class, 'checkPair'])->name('courses.check-pair');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::patch('/courses/{course}/toggle-active', [CourseController::class, 'toggleActive'])->name('courses.toggle-active');

        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
        Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::patch('/teachers/{teacher}/toggle-active', [TeacherController::class, 'toggleActive'])->name('teachers.toggle-active');
        Route::post('/teachers/{teacher}/courses', [TeacherCourseController::class, 'store'])->name('teachers.courses.store');
        Route::delete('/teacher-courses/{teacherCourse}', [TeacherCourseController::class, 'destroy'])->name('teacher-courses.destroy');
        Route::post('/teachers/{teacher}/availabilities', [TeacherAvailabilityController::class, 'store'])->name('teachers.availabilities.store');
        Route::delete('/teacher-availabilities/{teacherAvailability}', [TeacherAvailabilityController::class, 'destroy'])->name('teacher-availabilities.destroy');

        Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
        Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
        Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
        Route::patch('/classrooms/{classroom}/toggle-active', [ClassroomController::class, 'toggleActive'])->name('classrooms.toggle-active');

        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
        Route::patch('/shifts/{shift}/toggle-active', [ShiftController::class, 'toggleActive'])->name('shifts.toggle-active');
        Route::post('/shifts/{shift}/windows', [ShiftWindowController::class, 'store'])->name('shifts.windows.store');
        Route::delete('/shift-windows/{shiftWindow}', [ShiftWindowController::class, 'destroy'])->name('shifts.windows.destroy');
        Route::post('/shifts/{shift}/reserved-blocks', [ReservedBlockController::class, 'store'])->name('shifts.reserved-blocks.store');
        Route::delete('/reserved-blocks/{reservedBlock}', [ReservedBlockController::class, 'destroy'])->name('shifts.reserved-blocks.destroy');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/{setting}', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('/import', fn () => Inertia::render('SectionPlaceholder', [
        'title' => 'Importar historial desde Excel',
        'subtitle' => 'Carga de libros Matutino, Vespertino y Sabatino (hoja Materias-Maestro).',
        'phase' => 'Fase 5',
        'description' => 'Aquí subirás los archivos de Excel para detectar incidencias, mapear casos especiales y poblar el historial.',
    ]))->name('import.index');

    Route::get('/users', fn () => Inertia::render('SectionPlaceholder', [
        'title' => 'Gestión de usuarios',
        'subtitle' => 'Administración de cuentas y roles de acceso.',
        'phase' => 'Administración',
        'description' => 'Sección exclusiva para administradores destinada al alta y control de cuentas de coordinadores y administradores.',
    ]))->middleware('can:manage-users')->name('users.index');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
