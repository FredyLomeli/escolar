<?php

namespace App\Http\Controllers;

use App\Domain\Planning\AdvancePeriodAction;
use App\Domain\Planning\AdvancePeriodInput;
use App\Domain\Planning\PreviewPeriodAdvancementAction;
use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingSeverity;
use App\Domain\Scheduling\PeriodValidator;
use App\Domain\Scheduling\PublishPeriodAction;
use App\Enums\AcademicPeriodStatus;
use App\Http\Requests\Planning\StoreAcademicPeriodRequest;
use App\Models\AcademicPeriod;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPeriodController extends Controller
{
    public function wizard(Request $request, PreviewPeriodAdvancementAction $previewAction): Response
    {
        Gate::authorize('create', AcademicPeriod::class);

        $sourcePeriods = AcademicPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('term_of_year')
            ->orderByDesc('id')
            ->get();

        $requestedSourceId = $request->integer('source_period_id');
        $selectedSource = $requestedSourceId > 0
            ? $sourcePeriods->firstWhere('id', $requestedSourceId)
            : $sourcePeriods->first();

        $preview = $selectedSource !== null
            ? $previewAction->execute($selectedSource)
            : ['advancing' => [], 'graduating' => []];

        $suggested = $this->suggestNextPeriod($selectedSource);

        return Inertia::render('Periods/Wizard', [
            'sourcePeriods' => $sourcePeriods->map(fn (AcademicPeriod $period): array => [
                'id' => $period->id,
                'code' => $period->code,
                'name' => $period->name,
                'year' => $period->year,
                'term_of_year' => $period->term_of_year,
                'weeks' => $period->weeks,
                'status' => $period->status->value,
                'status_label' => $period->status->label(),
            ])->values(),
            'initialSourceId' => $selectedSource?->id,
            'initialPreview' => $preview,
            'suggested' => $suggested,
        ]);
    }

    public function preview(AcademicPeriod $academicPeriod, PreviewPeriodAdvancementAction $previewAction): JsonResponse
    {
        Gate::authorize('create', AcademicPeriod::class);

        return response()->json($previewAction->execute($academicPeriod));
    }

    public function store(StoreAcademicPeriodRequest $request, AdvancePeriodAction $advanceAction): RedirectResponse
    {
        $validated = $request->validated();
        $sourcePeriodId = $validated['source_period_id'] ?? null;

        if ($sourcePeriodId === null) {
            $period = AcademicPeriod::query()->create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'year' => (int) $validated['year'],
                'term_of_year' => (int) $validated['term_of_year'],
                'starts_on' => $validated['starts_on'],
                'ends_on' => $validated['ends_on'],
                'weeks' => (int) $validated['weeks'],
                'status' => AcademicPeriodStatus::Draft,
            ]);

            return redirect()
                ->route('dashboard', ['period_id' => $period->id])
                ->with('success', "Periodo {$period->code} creado correctamente.");
        }

        $overrides = [];
        foreach ($validated['student_count_overrides'] ?? [] as $groupId => $count) {
            $overrides[(int) $groupId] = (int) $count;
        }

        $excludedIds = array_values(array_map(
            static fn ($id): int => (int) $id,
            $validated['excluded_group_ids'] ?? [],
        ));

        $period = $advanceAction->execute(new AdvancePeriodInput(
            code: $validated['code'],
            name: $validated['name'],
            year: (int) $validated['year'],
            termOfYear: (int) $validated['term_of_year'],
            startsOn: $validated['starts_on'],
            endsOn: $validated['ends_on'],
            weeks: (int) $validated['weeks'],
            sourcePeriodId: (int) $sourcePeriodId,
            copyMeetings: (bool) $validated['copy_meetings'],
            suggestTeachers: (bool) $validated['suggest_teachers'],
            studentCountOverrides: $overrides,
            excludedGroupIds: $excludedIds,
        ));

        $period->loadCount(['groupTerms', 'sections']);

        return redirect()
            ->route('dashboard', ['period_id' => $period->id])
            ->with(
                'success',
                "Periodo {$period->code} creado con {$period->group_terms_count} grupo(s) y {$period->sections_count} sección(es) generada(s).",
            );
    }

    public function findings(
        Request $request,
        AcademicPeriod $academicPeriod,
        PeriodValidator $validator,
    ): JsonResponse {
        Gate::authorize('viewAny', AcademicPeriod::class);

        $report = $validator->validate($academicPeriod);
        $allowPublishWithWarnings = (bool) Setting::getValue('allow_publish_with_warnings', true);

        $sectionIds = [];
        $singleSectionId = $request->integer('section_id');

        if ($singleSectionId > 0) {
            $sectionIds[] = $singleSectionId;
        }

        $rawSectionIds = $request->input('section_ids', []);

        if (is_array($rawSectionIds)) {
            foreach ($rawSectionIds as $id) {
                $intId = (int) $id;

                if ($intId > 0) {
                    $sectionIds[] = $intId;
                }
            }
        }

        $sectionIds = array_values(array_unique($sectionIds));
        $targetReport = $sectionIds !== [] ? $report->forSections($sectionIds) : $report;

        $severityFilter = FindingSeverity::tryFrom((string) $request->string('severity'));
        $findings = $targetReport->findings;

        if ($severityFilter !== null) {
            $findings = array_values(array_filter(
                $findings,
                static fn (Finding $finding): bool => $finding->severity === $severityFilter,
            ));
        }

        $serializedFindings = array_map(
            static fn (Finding $finding): array => $finding->toArray(),
            $findings,
        );

        $payload = [
            'period' => [
                'id' => $academicPeriod->id,
                'code' => $academicPeriod->code,
                'name' => $academicPeriod->name,
                'status' => $academicPeriod->status->value,
                'status_label' => $academicPeriod->status->label(),
            ],
            'counts' => $report->counts(),
            'can_publish' => $report->canPublish($allowPublishWithWarnings),
            'allow_publish_with_warnings' => $allowPublishWithWarnings,
            'findings' => $serializedFindings,
        ];

        if ($sectionIds !== []) {
            $payload['affected_section_ids'] = $sectionIds;
            $payload['affected_findings'] = $serializedFindings;
        }

        return response()->json($payload);
    }

    public function publish(
        Request $request,
        AcademicPeriod $academicPeriod,
        PublishPeriodAction $publishAction,
    ): RedirectResponse|JsonResponse {
        Gate::authorize('update', $academicPeriod);

        $result = $publishAction->execute($academicPeriod);
        $allowPublishWithWarnings = (bool) Setting::getValue('allow_publish_with_warnings', true);

        if (! $result->published) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $result->message,
                    'counts' => $result->report->counts(),
                    'can_publish' => false,
                    'errors' => array_map(
                        static fn (Finding $finding): array => $finding->toArray(),
                        $result->report->errors(),
                    ),
                ], 422);
            }

            return back()->with('error', $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result->message,
                'period' => [
                    'id' => $academicPeriod->id,
                    'code' => $academicPeriod->code,
                    'status' => $result->currentStatus->value,
                    'status_label' => $result->currentStatus->label(),
                ],
                'counts' => $result->report->counts(),
                'can_publish' => $result->report->canPublish($allowPublishWithWarnings),
            ]);
        }

        return back()->with('success', $result->message);
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     year: int,
     *     term_of_year: int,
     *     starts_on: string,
     *     ends_on: string,
     *     weeks: int,
     * }
     */
    private function suggestNextPeriod(?AcademicPeriod $source): array
    {
        $regularWeeks = (int) Setting::getValue('weeks_in_term_regular', 14);
        $execModuleWeeks = (int) Setting::getValue('weeks_in_module_executive', 5);
        $execModules = (int) Setting::getValue('modules_per_term_executive', 3);
        $defaultWeeks = max($regularWeeks, $execModuleWeeks * $execModules);

        if ($source === null) {
            $year = (int) now()->year;

            return [
                'code' => "{$year}20",
                'name' => "Ene – May {$year}",
                'year' => $year,
                'term_of_year' => 1,
                'starts_on' => "{$year}-01-12",
                'ends_on' => "{$year}-05-01",
                'weeks' => $defaultWeeks,
            ];
        }

        $nextTerm = $source->term_of_year >= 3 ? 1 : $source->term_of_year + 1;
        $nextYear = $source->term_of_year >= 3 ? $source->year + 1 : $source->year;

        [$suffix, $monthsLabel, $startDate, $endDate] = match ($nextTerm) {
            1 => ['20', "Ene – May {$nextYear}", "{$nextYear}-01-11", "{$nextYear}-05-01"],
            2 => ['40', "May – Ago {$nextYear}", "{$nextYear}-05-11", "{$nextYear}-08-21"],
            default => ['60', "Ago – Dic {$nextYear}", "{$nextYear}-08-24", "{$nextYear}-12-11"],
        };

        return [
            'code' => "{$nextYear}{$suffix}",
            'name' => $monthsLabel,
            'year' => $nextYear,
            'term_of_year' => $nextTerm,
            'starts_on' => $startDate,
            'ends_on' => $endDate,
            'weeks' => $defaultWeeks,
        ];
    }
}
