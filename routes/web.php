<?php

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

        Route::get('/courses', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Catálogo de materias',
            'subtitle' => 'Materias identificadas por clave SUBJ + CRSE.',
            'phase' => 'Fase 1',
            'description' => 'Aquí registrarás y actualizarás las materias del catálogo institucional evitando claves duplicadas.',
        ]))->name('courses.index');

        Route::get('/teachers', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Docentes',
            'subtitle' => 'Profesores, perfiles de materias y disponibilidad semanal.',
            'phase' => 'Fase 1',
            'description' => 'Aquí gestionarás a los docentes por su ID único, las materias que pueden impartir y su cuadrícula de disponibilidad.',
        ]))->name('teachers.index');

        Route::get('/classrooms', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Aulas',
            'subtitle' => 'Espacios físicos y capacidades máximas.',
            'phase' => 'Fase 1',
            'description' => 'Aquí administrarás las aulas disponibles y su capacidad para validar contra el tamaño de cada sección.',
        ]))->name('classrooms.index');

        Route::get('/shifts', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Turnos y bloques reservados',
            'subtitle' => 'Ventanas horarias por modalidad y bloques protegidos (inglés).',
            'phase' => 'Fase 1',
            'description' => 'Aquí configurarás los turnos escolarizados y ejecutivos, sus horarios permitidos y los bloques reservados.',
        ]))->name('shifts.index');

        Route::get('/settings', fn () => Inertia::render('SectionPlaceholder', [
            'title' => 'Parámetros del sistema',
            'subtitle' => 'Reglas configurables de planeación y tamaños objetivo.',
            'phase' => 'Fase 1',
            'description' => 'Aquí ajustarás parámetros como tamaño objetivo por sección, semanas por modalidad e historial para propuestas.',
        ]))->name('settings.index');
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
