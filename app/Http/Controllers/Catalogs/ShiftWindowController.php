<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreShiftWindowRequest;
use App\Models\Shift;
use App\Models\ShiftWindow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ShiftWindowController extends Controller
{
    public function store(StoreShiftWindowRequest $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validated();
        $startsAt = $validated['starts_at'].':00';
        $endsAt = $validated['ends_at'].':00';

        foreach ($validated['days_of_week'] as $dayOfWeek) {
            ShiftWindow::query()->firstOrCreate([
                'shift_id' => $shift->id,
                'day_of_week' => (int) $dayOfWeek,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        return back()->with('success', 'Ventana horaria agregada correctamente.');
    }

    public function destroy(ShiftWindow $shiftWindow): RedirectResponse
    {
        $shiftWindow->load('shift');
        Gate::authorize('update', $shiftWindow->shift);

        $shiftWindow->delete();

        return back()->with('success', 'Ventana horaria eliminada.');
    }
}
