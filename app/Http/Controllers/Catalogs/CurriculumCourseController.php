<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreCurriculumCourseRequest;
use App\Http\Requests\Catalogs\UpdateCurriculumCourseRequest;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CurriculumCourseController extends Controller
{
    public function store(StoreCurriculumCourseRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $curriculum->curriculumCourses()->create($request->validated());

        return back()->with('success', 'Materia agregada al plan de estudios.');
    }

    public function update(UpdateCurriculumCourseRequest $request, CurriculumCourse $curriculumCourse): RedirectResponse
    {
        $curriculumCourse->update($request->validated());

        return back()->with('success', 'Materia actualizada en el plan.');
    }

    public function destroy(CurriculumCourse $curriculumCourse): RedirectResponse
    {
        $curriculumCourse->load('curriculum');
        Gate::authorize('update', $curriculumCourse->curriculum);

        $curriculumCourse->delete();

        return back()->with('success', 'Materia retirada del plan de estudios.');
    }
}
