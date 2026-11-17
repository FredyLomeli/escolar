<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreTeacherAvailabilityRequest;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TeacherAvailabilityController extends Controller
{
    public function store(StoreTeacherAvailabilityRequest $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validated();
        $startsAt = $validated['starts_at'].':00';
        $endsAt = $validated['ends_at'].':00';

        foreach ($validated['days_of_week'] as $dayOfWeek) {
            TeacherAvailability::query()->firstOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'academic_period_id' => null,
                    'day_of_week' => (int) $dayOfWeek,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ],
                [
                    'type' => $validated['type'],
                ],
            );
        }

        return back()->with('success', 'Disponibilidad agregada correctamente.');
    }

    public function destroy(TeacherAvailability $teacherAvailability): RedirectResponse
    {
        $teacherAvailability->load('teacher');
        Gate::authorize('update', $teacherAvailability->teacher);

        $teacherAvailability->delete();

        return back()->with('success', 'Franja de disponibilidad eliminada.');
    }
}
