<?php

namespace App\Http\Controllers\Catalogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreClassroomRequest;
use App\Http\Requests\Catalogs\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        $search = trim((string) $request->string('search'));
        $status = (string) $request->string('status', 'all');

        $classrooms = Classroom::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('active', false))
            ->orderByDesc('active')
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Catalogs/Classrooms/Index', [
            'classrooms' => $classrooms,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        Classroom::query()->create($request->validated());

        return back()->with('success', 'Aula registrada correctamente.');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return back()->with('success', 'Aula actualizada correctamente.');
    }

    public function toggleActive(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('update', $classroom);

        $classroom->update([
            'active' => ! $classroom->active,
        ]);

        $message = $classroom->active
            ? 'Aula reactivada correctamente.'
            : 'Aula desactivada correctamente.';

        return back()->with('success', $message);
    }
}
