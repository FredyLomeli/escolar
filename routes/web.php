<?php

use App\Http\Controllers\Catalogs\ClassroomController;
use App\Http\Controllers\Catalogs\CourseController;
use App\Http\Controllers\Catalogs\ReservedBlockController;
use App\Http\Controllers\Catalogs\SettingController;
use App\Http\Controllers\Catalogs\ShiftController;
use App\Http\Controllers\Catalogs\ShiftWindowController;
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
        Route::get('/programs', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Carreras y planes de estudio',
            'subtitle' => 'Programas académicos, versiones de planes y malla curricular por cuatrimestre.',
            'phase' => 'Fase 1',
            'description' => 'Aquí administrarás las carreras, sus planes versionados y la malla de materias del 1.º al 9.º cuatrimestre.',
        ]))->name('programs.index');

        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/check-pair', [CourseController::class, 'checkPair'])->name('courses.check-pair');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::patch('/courses/{course}/toggle-active', [CourseController::class, 'toggleActive'])->name('courses.toggle-active');

        Route::get('/teachers', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Docentes',
            'subtitle' => 'Profesores, perfiles de materias y disponibilidad semanal.',
            'phase' => 'Fase 1',
            'description' => 'Aquí gestionarás a los docentes por su ID único, las materias que pueden impartir y su cuadrícula de disponibilidad.',
        ]))->name('teachers.index');

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
