<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreCourseRequest;
use App\Http\Requests\Catalogs\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', 'all');

        $courses = Course::query()
            ->with(['curriculumCourses.curriculum.program'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('subject_code', 'like', "%{$search}%")
                        ->orWhere('course_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('active')
            ->orderBy('subject_code')
            ->orderBy('course_code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogs/Courses/Index', [
            'courses' => $courses,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Course::query()->create($request->validated());

        return back()->with('success', 'Materia registrada correctamente.');
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return back()->with('success', 'Materia actualizada correctamente.');
    }

    public function toggleActive(Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $course->update([
            'active' => ! $course->active,
        ]);

        $message = $course->active
            ? 'Materia reactivada correctamente.'
            : 'Materia desactivada correctamente.';

        return back()->with('success', $message);
    }

    public function checkPair(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Course::class);

        $subjectCode = strtoupper(trim((string) $request->string('subject_code')));
        $courseCode = strtoupper(trim((string) $request->string('course_code')));
        $ignoreId = $request->integer('ignore_id');

        if ($subjectCode === '' || $courseCode === '') {
            return response()->json(['exists' => false, 'course' => null]);
        }

        $existing = Course::query()
            ->where('subject_code', $subjectCode)
            ->where('course_code', $courseCode)
            ->when($ignoreId > 0, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->first(['id', 'subject_code', 'course_code', 'name', 'active']);

        return response()->json([
            'exists' => $existing !== null,
            'course' => $existing,
        ]);
    }
}
