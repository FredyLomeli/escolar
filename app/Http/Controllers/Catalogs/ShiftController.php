<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreShiftRequest;
use App\Http\Requests\Catalogs\UpdateShiftRequest;
use App\Models\Modality;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Shift::class);

        $modalityId = $request->integer('modality_id');
        $status = (string) $request->string('status', 'all');

        $shifts = Shift::query()
            ->with([
                'modality',
                'windows' => function (HasMany $query): void {
                    $query->orderBy('day_of_week')->orderBy('starts_at');
                },
                'reservedBlocks' => function (HasMany $query): void {
                    $query->orderBy('day_of_week')->orderBy('starts_at');
                },
            ])
            ->when($modalityId > 0, fn ($query) => $query->where('modality_id', $modalityId))
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('active')
            ->orderBy('modality_id')
            ->orderBy('name')
            ->get();

        $modalities = Modality::query()
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Catalogs/Shifts/Index', [
            'shifts' => $shifts,
            'modalities' => $modalities,
            'filters' => [
                'modality_id' => $modalityId > 0 ? $modalityId : null,
                'status' => $status,
            ],
        ]);
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        Shift::query()->create($request->validated());

        return back()->with('success', 'Turno registrado correctamente.');
    }

    public function update(UpdateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->validated());

        return back()->with('success', 'Turno actualizado correctamente.');
    }

    public function toggleActive(Shift $shift): RedirectResponse
    {
        Gate::authorize('update', $shift);

        $shift->update([
            'active' => ! $shift->active,
        ]);

        $message = $shift->active
            ? 'Turno reactivado correctamente.'
            : 'Turno desactivado correctamente.';

        return back()->with('success', $message);
    }
}
