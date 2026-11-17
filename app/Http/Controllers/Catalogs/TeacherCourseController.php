<?php

namespace App\Http\Controllers\Catalogs;

use App\Enums\TeacherCourseSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreTeacherCourseRequest;
use App\Models\Teacher;
use App\Models\TeacherCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TeacherCourseController extends Controller
{
    public function store(StoreTeacherCourseRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->teacherCourses()->create([
            'course_id' => $request->validated('course_id'),
            'source' => TeacherCourseSource::Manual,
        ]);

        return back()->with('success', 'Materia agregada al perfil del docente.');
    }

    public function destroy(TeacherCourse $teacherCourse): RedirectResponse
    {
        $teacherCourse->load('teacher');
        Gate::authorize('update', $teacherCourse->teacher);

        $teacherCourse->delete();

        return back()->with('success', 'Materia retirada del perfil del docente.');
    }
}
