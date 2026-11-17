<?php

namespace App\Http\Controllers;

use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingSeverity;
use App\Domain\Scheduling\PeriodValidator;
use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Enums\SectionStatus;
use App\Http\Requests\Planning\StoreSectionRequest;
use App\Http\Requests\Planning\UpdateSectionRequest;
use App\Models\AcademicPeriod;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(Request $request, PeriodValidator $validator): Response
    {
        Gate::authorize('viewAny', Section::class);

        $periods = AcademicPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('term_of_year')
            ->orderByDesc('id')
            ->get(['id', 'code', 'name', 'year', 'term_of_year', 'weeks', 'status']);

        $requestedPeriodId = $request->integer('period_id');
        $selectedPeriod = $requestedPeriodId > 0
            ? $periods->firstWhere('id', $requestedPeriodId)
            : ($periods->firstWhere('status', AcademicPeriodStatus::Draft) ?? $periods->first());

        $search = trim((string) $request->string('search'));
        $groupTermId = $request->integer('group_term_id');
        $delivery = (string) $request->string('delivery', 'all');
        $pending = (string) $request->string('pending', 'all');

        $sections = collect();
        $groupTerms = collect();
        $validationSummary = null;
        $periodFindings = [];
        /** @var array<int, list<array<string, mixed>>> $findingsBySection */
        $findingsBySection = [];

        if ($selectedPeriod !== null) {
            $report = $validator->validate($selectedPeriod);
            $allowPublishWithWarnings = (bool) Setting::getValue('allow_publish_with_warnings', true);
            $validationSummary = [
                'counts' => $report->counts(),
                'can_publish' => $report->canPublish($allowPublishWithWarnings),
                'allow_publish_with_warnings' => $allowPublishWithWarnings,
            ];

            /** @var array<int, true> $errorSectionLookup */
            $errorSectionLookup = [];
            /** @var array<int, true> $warningSectionLookup */
            $warningSectionLookup = [];

            foreach ($report->findings as $finding) {
                $serialized = $finding->toArray();
                $periodFindings[] = $serialized;

                foreach ($finding->sectionIds as $sectionId) {
                    $findingsBySection[$sectionId][] = $serialized;

                    if ($finding->severity === FindingSeverity::Error) {
                        $errorSectionLookup[$sectionId] = true;
                    } elseif ($finding->severity === FindingSeverity::Warning) {
                        $warningSectionLookup[$sectionId] = true;
                    }
                }
            }

            $groupTerms = GroupTerm::query()
                ->with(['studentGroup.program', 'studentGroup.shift'])
                ->where('academic_period_id', $selectedPeriod->id)
                ->orderBy('term_number')
                ->orderBy('id')
                ->get();

            $sections = Section::query()
                ->with([
                    'course',
                    'teacher',
                    'shift.modality',
                    'groupTerms.studentGroup.program',
                    'groupTerms.studentGroup.shift',
                    'meetings' => fn ($query) => $query
                        ->with('classroom')
                        ->orderBy('day_of_week')
                        ->orderBy('starts_at'),
                ])
                ->where('academic_period_id', $selectedPeriod->id)
                ->when($groupTermId > 0, function ($query) use ($groupTermId): void {
                    $query->whereHas('groupTerms', fn ($groupQuery) => $groupQuery->where('group_terms.id', $groupTermId));
                })
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($subQuery) use ($search): void {
                        $subQuery->where('crn', 'like', "%{$search}%")
                            ->orWhereHas('course', function ($courseQuery) use ($search): void {
                                $courseQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('subject_code', 'like', "%{$search}%")
                                    ->orWhere('course_code', 'like', "%{$search}%");
                            });
                    });
                })
                ->when(
                    $delivery !== 'all' && DeliveryType::tryFrom($delivery) !== null,
                    fn ($query) => $query->where('delivery', $delivery),
                )
                ->when($pending === 'without_teacher', function ($query): void {
                    $query->where('delivery', DeliveryType::InPerson->value)
                        ->whereNull('teacher_id');
                })
                ->when($pending === 'without_schedule', function ($query): void {
                    $query->where(function ($subQuery): void {
                        $subQuery->whereDoesntHave('meetings')
                            ->orWhereHas('meetings', fn ($meetingQuery) => $meetingQuery->whereNull('classroom_id'));
                    });
                })
                ->orderBy('course_id')
                ->orderBy('id')
                ->get()
                ->when(
                    $pending === 'with_errors',
                    fn ($collection) => $collection->filter(
                        static fn (Section $section): bool => isset($errorSectionLookup[$section->id]),
                    ),
                )
                ->when(
                    $pending === 'with_warnings',
                    fn ($collection) => $collection->filter(
                        static fn (Section $section): bool => isset($warningSectionLookup[$section->id]),
                    ),
                )
                ->map(fn (Section $section): array => [
                    'id' => $section->id,
                    'academic_period_id' => $section->academic_period_id,
                    'course_id' => $section->course_id,
                    'crn' => $section->crn,
                    'teacher_id' => $section->teacher_id,
                    'delivery' => $section->delivery->value,
                    'delivery_label' => $section->delivery->label(),
                    'is_online' => $section->delivery->isOnline(),
                    'shift_id' => $section->shift_id,
                    'start_week' => $section->start_week,
                    'end_week' => $section->end_week,
                    'students_override' => $section->students_override,
                    'inherited_students' => (int) $section->groupTerms->sum('student_count'),
                    'effective_students' => $section->studentCount(),
                    'is_compacted' => $section->isCompacted(),
                    'status' => $section->status->value,
                    'status_label' => $section->status->label(),
                    'notes' => $section->notes,
                    'findings' => $findingsBySection[$section->id] ?? [],
                    'course' => [
                        'id' => $section->course->id,
                        'subject_code' => $section->course->subject_code,
                        'course_code' => $section->course->course_code,
                        'name' => $section->course->name,
                    ],
                    'teacher' => $section->teacher ? [
                        'id' => $section->teacher->id,
                        'employee_id' => $section->teacher->employee_id,
                        'full_name' => $section->teacher->full_name,
                    ] : null,
                    'shift' => [
                        'id' => $section->shift->id,
                        'name' => $section->shift->name,
                        'modality_name' => $section->shift->modality->name,
                    ],
                    'group_terms' => $section->groupTerms->map(fn (GroupTerm $groupTerm): array => [
                        'id' => $groupTerm->id,
                        'term_number' => $groupTerm->term_number,
                        'student_count' => $groupTerm->student_count,
                        'label' => $groupTerm->studentGroup->label
                            ?: "{$groupTerm->studentGroup->program->code} · {$groupTerm->term_number}.º",
                        'program_code' => $groupTerm->studentGroup->program->code,
                        'shift_name' => $groupTerm->studentGroup->shift->name,
                    ])->values(),
                    'meetings' => $section->meetings->map(fn ($meeting): array => [
                        'id' => $meeting->id,
                        'day_of_week' => $meeting->day_of_week,
                        'starts_at' => substr((string) $meeting->starts_at, 0, 5),
                        'ends_at' => substr((string) $meeting->ends_at, 0, 5),
                        'classroom_id' => $meeting->classroom_id,
                        'classroom' => $meeting->classroom ? [
                            'id' => $meeting->classroom->id,
                            'code' => $meeting->classroom->code,
                            'name' => $meeting->classroom->name,
                            'capacity' => $meeting->classroom->capacity,
                        ] : null,
                    ])->values(),
                ])
                ->values();
        }

        $teachers = Teacher::query()
            ->where('active', true)
            ->orderBy('full_name')
            ->get(['id', 'employee_id', 'full_name', 'origin']);

        $classrooms = Classroom::query()
            ->where('active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'capacity']);

        $shifts = Shift::query()
            ->where('active', true)
            ->with('modality:id,code,name')
            ->orderBy('modality_id')
            ->orderBy('name')
            ->get(['id', 'modality_id', 'code', 'name']);

        $courses = Course::query()
            ->where('active', true)
            ->orderBy('subject_code')
            ->orderBy('course_code')
            ->get(['id', 'subject_code', 'course_code', 'name']);

        return Inertia::render('Sections/Index', [
            'periods' => $periods->map(fn (AcademicPeriod $period): array => [
                'id' => $period->id,
                'code' => $period->code,
                'name' => $period->name,
                'weeks' => $period->weeks,
                'status' => $period->status->value,
                'status_label' => $period->status->label(),
            ])->values(),
            'selectedPeriod' => $selectedPeriod ? [
                'id' => $selectedPeriod->id,
                'code' => $selectedPeriod->code,
                'name' => $selectedPeriod->name,
                'weeks' => $selectedPeriod->weeks,
                'status' => $selectedPeriod->status->value,
                'status_label' => $selectedPeriod->status->label(),
            ] : null,
            'sections' => $sections,
            'validationSummary' => $validationSummary,
            'findings' => $periodFindings,
            'groupTerms' => $groupTerms->map(fn (GroupTerm $groupTerm): array => [
                'id' => $groupTerm->id,
                'shift_id' => $groupTerm->studentGroup->shift_id,
                'term_number' => $groupTerm->term_number,
                'student_count' => $groupTerm->student_count,
                'label' => $groupTerm->studentGroup->label
                    ?: "{$groupTerm->studentGroup->program->code} · {$groupTerm->term_number}.º",
                'program_name' => "{$groupTerm->studentGroup->program->code} — {$groupTerm->studentGroup->program->name}",
                'shift_name' => $groupTerm->studentGroup->shift->name,
            ])->values(),
            'teachers' => $teachers,
            'classrooms' => $classrooms,
            'shifts' => $shifts->map(fn (Shift $shift): array => [
                'id' => $shift->id,
                'modality_id' => $shift->modality_id,
                'code' => $shift->code,
                'name' => $shift->name,
                'modality_name' => $shift->modality->name,
            ])->values(),
            'courses' => $courses,
            'deliveryOptions' => array_map(fn (DeliveryType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'is_online' => $type->isOnline(),
            ], DeliveryType::cases()),
            'statusOptions' => array_map(fn (SectionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], SectionStatus::cases()),
            'filters' => [
                'search' => $search,
                'group_term_id' => $groupTermId > 0 ? $groupTermId : null,
                'delivery' => $delivery,
                'pending' => $pending,
            ],
        ]);
    }

    public function store(StoreSectionRequest $request, PeriodValidator $validator): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        /** @var array<int, array<string, mixed>> $meetings */
        $meetings = $validated['meetings'];

        /** @var Section $section */
        $section = DB::transaction(function () use ($validated, $meetings): Section {
            $created = Section::query()->create([
                'academic_period_id' => (int) $validated['academic_period_id'],
                'course_id' => (int) $validated['course_id'],
                'crn' => $validated['crn'],
                'teacher_id' => $validated['teacher_id'] !== null ? (int) $validated['teacher_id'] : null,
                'delivery' => $validated['delivery'],
                'shift_id' => (int) $validated['shift_id'],
                'start_week' => (int) $validated['start_week'],
                'end_week' => (int) $validated['end_week'],
                'students_override' => $validated['students_override'] !== null
                    ? (int) $validated['students_override']
                    : null,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
            ]);

            $created->groupTerms()->attach((int) $validated['group_term_id']);
            $this->syncMeetings($created, $meetings);

            return $created;
        });

        if ($request->expectsJson()) {
            return response()->json($this->buildSectionValidationPayload($section, $validator, 'Clase creada correctamente.'), 201);
        }

        return back()->with('success', 'Clase creada correctamente.');
    }

    public function update(
        UpdateSectionRequest $request,
        Section $section,
        PeriodValidator $validator,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        /** @var array<int, array<string, mixed>> $meetings */
        $meetings = $validated['meetings'];

        DB::transaction(function () use ($section, $validated, $meetings): void {
            $section->update([
                'crn' => $validated['crn'],
                'teacher_id' => $validated['teacher_id'] !== null ? (int) $validated['teacher_id'] : null,
                'delivery' => $validated['delivery'],
                'shift_id' => (int) $validated['shift_id'],
                'start_week' => (int) $validated['start_week'],
                'end_week' => (int) $validated['end_week'],
                'students_override' => $validated['students_override'] !== null
                    ? (int) $validated['students_override']
                    : null,
                'status' => $validated['status'],
                'notes' => $validated['notes'],
            ]);

            $this->syncMeetings($section, $meetings);
        });

        if ($request->expectsJson()) {
            return response()->json($this->buildSectionValidationPayload($section, $validator, 'Clase actualizada correctamente.'));
        }

        return back()->with('success', 'Clase actualizada correctamente.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        Gate::authorize('delete', $section);

        $section->delete();

        return back()->with('success', 'Clase eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSectionValidationPayload(
        Section $section,
        PeriodValidator $validator,
        string $message,
    ): array {
        $period = AcademicPeriod::query()->findOrFail($section->academic_period_id);
        $report = $validator->validate($period);
        $allowPublishWithWarnings = (bool) Setting::getValue('allow_publish_with_warnings', true);

        return [
            'message' => $message,
            'section_id' => $section->id,
            'counts' => $report->counts(),
            'can_publish' => $report->canPublish($allowPublishWithWarnings),
            'affected_findings' => array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $report->forSection($section->id)->findings,
            ),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $meetings
     */
    private function syncMeetings(Section $section, array $meetings): void
    {
        $section->meetings()->delete();

        if ($meetings === []) {
            return;
        }

        usort(
            $meetings,
            fn (array $a, array $b): int => [(int) $a['day_of_week'], (string) $a['starts_at']]
                <=> [(int) $b['day_of_week'], (string) $b['starts_at']],
        );

        $records = array_map(fn (array $meeting): array => [
            'day_of_week' => (int) $meeting['day_of_week'],
            'starts_at' => (string) $meeting['starts_at'],
            'ends_at' => (string) $meeting['ends_at'],
            'classroom_id' => isset($meeting['classroom_id'])
                ? (int) $meeting['classroom_id']
                : null,
        ], $meetings);

        $section->meetings()->createMany($records);
    }
}
