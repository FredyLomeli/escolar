<?php

namespace App\Http\Controllers\Catalogs;

use App\Enums\AvailabilityType;
use App\Enums\TeacherCourseSource;
use App\Enums\TeacherOrigin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreTeacherRequest;
use App\Http\Requests\Catalogs\UpdateTeacherRequest;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Teacher::class);

        $search = trim((string) $request->string('search'));
        $origin = (string) $request->string('origin', 'all');
        $status = (string) $request->string('status', 'all');

        $teachers = Teacher::query()
            ->withCount(['teacherCourses', 'availabilities'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('employee_id', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($origin, [TeacherOrigin::Campus->value, TeacherOrigin::External->value], true), function ($query) use ($origin): void {
                $query->where('origin', $origin);
            })
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('active')
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogs/Teachers/Index', [
            'teachers' => $teachers,
            'originOptions' => $this->originOptions(),
            'filters' => [
                'search' => $search,
                'origin' => $origin,
                'status' => $status,
            ],
        ]);
    }

    public function show(Teacher $teacher): Response
    {
        Gate::authorize('view', $teacher);

        $teacher->load([
            'teacherCourses' => function (HasMany $query): void {
                $query->with('course')->orderBy('id');
            },
            'availabilities' => function (HasMany $query): void {
                $query->whereNull('academic_period_id')
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at');
            },
        ]);

        $assignedCourseIds = $teacher->teacherCourses->pluck('course_id');

        $availableCourses = Course::query()
            ->where('active', true)
            ->whereNotIn('id', $assignedCourseIds)
            ->orderBy('subject_code')
            ->orderBy('course_code')
            ->get(['id', 'subject_code', 'course_code', 'name']);

        $availabilityTypeOptions = collect(AvailabilityType::cases())
            ->map(fn (AvailabilityType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values();

        $sourceOptions = collect(TeacherCourseSource::cases())
            ->map(fn (TeacherCourseSource $source): array => [
                'value' => $source->value,
                'label' => $source->label(),
            ])
            ->values();

        return Inertia::render('Catalogs/Teachers/Show', [
            'teacher' => $teacher,
            'availableCourses' => $availableCourses,
            'originOptions' => $this->originOptions(),
            'availabilityTypeOptions' => $availabilityTypeOptions,
            'sourceOptions' => $sourceOptions,
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        Teacher::query()->create($request->validated());

        return back()->with('success', 'Docente registrado correctamente.');
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update($request->validated());

        return back()->with('success', 'Docente actualizado correctamente.');
    }

    public function toggleActive(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('update', $teacher);

        $teacher->update([
            'active' => ! $teacher->active,
        ]);

        $message = $teacher->active
            ? 'Docente reactivado correctamente.'
            : 'Docente desactivado correctamente.';

        return back()->with('success', $message);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function originOptions(): array
    {
        return collect(TeacherOrigin::cases())
            ->map(fn (TeacherOrigin $origin): array => [
                'value' => $origin->value,
                'label' => $origin->label(),
            ])
            ->values()
            ->all();
    }
}
