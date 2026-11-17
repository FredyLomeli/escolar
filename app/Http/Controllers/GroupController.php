<?php

namespace App\Http\Controllers;

use App\Domain\Planning\GenerateSectionsForGroupTermAction;
use App\Enums\AcademicPeriodStatus;
use App\Enums\StudentGroupStatus;
use App\Http\Requests\Planning\BulkUpdateGroupTermsRequest;
use App\Http\Requests\Planning\StoreStudentGroupRequest;
use App\Models\AcademicPeriod;
use App\Models\GroupTerm;
use App\Models\Program;
use App\Models\Shift;
use App\Models\StudentGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GroupTerm::class);

        $periods = AcademicPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('term_of_year')
            ->orderByDesc('id')
            ->get(['id', 'code', 'name', 'status']);

        $requestedPeriodId = $request->integer('period_id');
        $selectedPeriod = $requestedPeriodId > 0
            ? $periods->firstWhere('id', $requestedPeriodId)
            : ($periods->firstWhere('status', AcademicPeriodStatus::Draft) ?? $periods->first());

        $search = trim((string) $request->string('search'));
        $programId = $request->integer('program_id');
        $confirmed = (string) $request->string('confirmed', 'all');

        $groupTerms = collect();

        if ($selectedPeriod !== null) {
            $groupTerms = GroupTerm::query()
                ->with([
                    'studentGroup.program.modality',
                    'studentGroup.curriculum',
                    'studentGroup.shift',
                ])
                ->withCount('sections')
                ->where('academic_period_id', $selectedPeriod->id)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->whereHas('studentGroup', function ($groupQuery) use ($search): void {
                        $groupQuery->where('label', 'like', "%{$search}%")
                            ->orWhereHas('program', function ($programQuery) use ($search): void {
                                $programQuery->where('code', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($programId > 0, function ($query) use ($programId): void {
                    $query->whereHas('studentGroup', fn ($groupQuery) => $groupQuery->where('program_id', $programId));
                })
                ->when($confirmed === 'confirmed', fn ($query) => $query->where('student_count_confirmed', true))
                ->when($confirmed === 'unconfirmed', fn ($query) => $query->where('student_count_confirmed', false))
                ->orderBy('term_number')
                ->orderBy('id')
                ->get();
        }

        $programs = Program::query()
            ->where('active', true)
            ->with([
                'modality:id,code,name',
                'curricula' => function (HasMany $query): void {
                    $query->where('active', true)
                        ->orderByDesc('version')
                        ->select(['id', 'program_id', 'version']);
                },
            ])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'modality_id']);

        $shifts = Shift::query()
            ->where('active', true)
            ->orderBy('modality_id')
            ->orderBy('name')
            ->get(['id', 'modality_id', 'code', 'name']);

        return Inertia::render('Groups/Index', [
            'periods' => $periods->map(fn (AcademicPeriod $period): array => [
                'id' => $period->id,
                'code' => $period->code,
                'name' => $period->name,
                'status' => $period->status->value,
                'status_label' => $period->status->label(),
            ])->values(),
            'selectedPeriod' => $selectedPeriod ? [
                'id' => $selectedPeriod->id,
                'code' => $selectedPeriod->code,
                'name' => $selectedPeriod->name,
                'status' => $selectedPeriod->status->value,
                'status_label' => $selectedPeriod->status->label(),
            ] : null,
            'groupTerms' => $groupTerms,
            'programs' => $programs,
            'shifts' => $shifts,
            'filters' => [
                'search' => $search,
                'program_id' => $programId > 0 ? $programId : null,
                'confirmed' => $confirmed,
            ],
        ]);
    }

    public function store(
        StoreStudentGroupRequest $request,
        GenerateSectionsForGroupTermAction $generateSections,
    ): RedirectResponse {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated, $generateSections): array {
            $period = AcademicPeriod::query()->findOrFail($validated['academic_period_id']);

            $studentGroup = StudentGroup::query()->create([
                'program_id' => (int) $validated['program_id'],
                'curriculum_id' => (int) $validated['curriculum_id'],
                'shift_id' => (int) $validated['shift_id'],
                'entry_period_id' => $period->id,
                'label' => $validated['label'],
                'status' => StudentGroupStatus::Active,
            ]);

            $groupTerm = GroupTerm::query()->create([
                'student_group_id' => $studentGroup->id,
                'academic_period_id' => $period->id,
                'term_number' => (int) $validated['term_number'],
                'student_count' => (int) $validated['student_count'],
                'student_count_confirmed' => (bool) $validated['student_count_confirmed'],
            ]);

            $generatedCount = 0;

            if ($validated['generate_sections']) {
                $sections = $generateSections->execute(
                    groupTerm: $groupTerm,
                    targetPeriod: $period,
                    suggestTeachers: (bool) $validated['suggest_teachers'],
                );
                $generatedCount = $sections->count();
            }

            return [
                'period' => $period,
                'generatedCount' => $generatedCount,
            ];
        });

        $message = $result['generatedCount'] > 0
            ? "Grupo creado correctamente con {$result['generatedCount']} clase(s) generada(s) desde su plan."
            : 'Grupo creado correctamente.';

        return redirect()
            ->route('groups.index', ['period_id' => $result['period']->id])
            ->with('success', $message);
    }

    public function bulkUpdate(BulkUpdateGroupTermsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            foreach ($validated['items'] as $item) {
                GroupTerm::query()
                    ->where('id', (int) $item['id'])
                    ->where('academic_period_id', (int) $validated['academic_period_id'])
                    ->update([
                        'student_count' => (int) $item['student_count'],
                        'student_count_confirmed' => (bool) $item['student_count_confirmed'],
                    ]);
            }
        });

        return back()->with('success', 'Matrícula de grupos actualizada correctamente.');
    }
}
