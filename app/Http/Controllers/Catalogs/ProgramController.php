<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreProgramRequest;
use App\Http\Requests\Catalogs\UpdateProgramRequest;
use App\Models\Modality;
use App\Models\Program;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Program::class);

        $search = trim((string) $request->string('search'));
        $modalityId = $request->integer('modality_id');
        $status = (string) $request->string('status', 'all');

        $programs = Program::query()
            ->with([
                'modality',
                'curricula' => function (HasMany $query): void {
                    $query->withCount('curriculumCourses')
                        ->orderByDesc('active')
                        ->orderByDesc('version');
                },
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($modalityId > 0, fn ($query) => $query->where('modality_id', $modalityId))
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('active')
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        $modalities = Modality::query()
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Catalogs/Programs/Index', [
            'programs' => $programs,
            'modalities' => $modalities,
            'filters' => [
                'search' => $search,
                'modality_id' => $modalityId > 0 ? $modalityId : null,
                'status' => $status,
            ],
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::query()->create($request->validated());

        return back()->with('success', 'Carrera registrada correctamente.');
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return back()->with('success', 'Carrera actualizada correctamente.');
    }

    public function toggleActive(Program $program): RedirectResponse
    {
        Gate::authorize('update', $program);

        $program->update([
            'active' => ! $program->active,
        ]);

        $message = $program->active
            ? 'Carrera reactivada correctamente.'
            : 'Carrera desactivada correctamente.';

        return back()->with('success', $message);
    }
}
