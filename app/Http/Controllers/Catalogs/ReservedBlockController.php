<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreReservedBlockRequest;
use App\Models\ReservedBlock;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReservedBlockController extends Controller
{
    public function store(StoreReservedBlockRequest $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validated();
        $startsAt = $validated['starts_at'].':00';
        $endsAt = $validated['ends_at'].':00';

        foreach ($validated['days_of_week'] as $dayOfWeek) {
            ReservedBlock::query()->firstOrCreate([
                'shift_id' => $shift->id,
                'name' => $validated['name'],
                'day_of_week' => (int) $dayOfWeek,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
        }

        return back()->with('success', 'Bloque reservado agregado correctamente.');
    }

    public function destroy(ReservedBlock $reservedBlock): RedirectResponse
    {
        $reservedBlock->load('shift');
        Gate::authorize('update', $reservedBlock->shift);

        $reservedBlock->delete();

        return back()->with('success', 'Bloque reservado eliminado.');
    }
}
