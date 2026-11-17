<?php

namespace App\Http\Controllers\Catalogs;

use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Enums\ModalityCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalogs\StoreCurriculumRequest;
use App\Http\Requests\Catalogs\UpdateCurriculumRequest;
use App\Http\Requests\Catalogs\UpdateCurriculumTermRuleRequest;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\CurriculumTermRule;
use App\Models\Program;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function show(Curriculum $curriculum): Response
    {
        Gate::authorize('view', $curriculum);

        $curriculum->load([
            'program.modality',
            'termRules' => function (HasMany $query): void {
                $query->orderBy('term_number');
            },
            'curriculumCourses' => function (HasMany $query): void {
                $query->with('course')
                    ->orderBy('term_number')
                    ->orderBy('id');
            },
        ]);

        $assignedCourseIds = $curriculum->curriculumCourses->pluck('course_id');

        $availableCourses = Course::query()
            ->where('active', true)
            ->whereNotIn('id', $assignedCourseIds)
            ->orderBy('subject_code')
            ->orderBy('course_code')
            ->get(['id', 'subject_code', 'course_code', 'name']);

        $kindOptions = collect(CourseKind::cases())
            ->map(fn (CourseKind $kind): array => [
                'value' => $kind->value,
                'label' => $kind->label(),
            ])
            ->values();

        $deliveryOptions = collect(DeliveryType::cases())
            ->map(fn (DeliveryType $delivery): array => [
                'value' => $delivery->value,
                'label' => $delivery->label(),
            ])
            ->values();

        return Inertia::render('Catalogs/Curricula/Show', [
            'curriculum' => $curriculum,
            'availableCourses' => $availableCourses,
            'kindOptions' => $kindOptions,
            'deliveryOptions' => $deliveryOptions,
        ]);
    }

    public function store(StoreCurriculumRequest $request, Program $program): RedirectResponse
    {
        $validated = $request->validated();
        $program->load('modality');

        $curriculum = DB::transaction(function () use ($validated, $program): Curriculum {
            $newCurriculum = Curriculum::query()->create([
                'program_id' => $program->id,
                'version' => $validated['version'],
                'valid_from' => $validated['valid_from'] ?? null,
                'valid_to' => $validated['valid_to'] ?? null,
                'active' => true,
            ]);

            $sourceId = $validated['clone_from_curriculum_id'] ?? null;

            if ($sourceId !== null) {
                $source = Curriculum::query()
                    ->with(['termRules', 'curriculumCourses'])
                    ->findOrFail($sourceId);

                foreach ($source->termRules as $rule) {
                    CurriculumTermRule::query()->create([
                        'curriculum_id' => $newCurriculum->id,
                        'term_number' => $rule->term_number,
                        'expected_courses' => $rule->expected_courses,
                    ]);
                }

                foreach ($source->curriculumCourses as $item) {
                    CurriculumCourse::query()->create([
                        'curriculum_id' => $newCurriculum->id,
                        'course_id' => $item->course_id,
                        'term_number' => $item->term_number,
                        'kind' => $item->kind,
                        'default_delivery' => $item->default_delivery,
                        'weekly_hours' => $item->weekly_hours,
                        'display_name' => $item->display_name,
                    ]);
                }
            } else {
                $isRegular = $program->modality->code === ModalityCode::Regular;

                foreach (range(1, 9) as $term) {
                    $expected = match (true) {
                        $term === 7 && $isRegular => 1,
                        $term === 7 => 4,
                        in_array($term, [1, 2], true) && $isRegular => 7,
                        default => 5,
                    };

                    CurriculumTermRule::query()->create([
                        'curriculum_id' => $newCurriculum->id,
                        'term_number' => $term,
                        'expected_courses' => $expected,
                    ]);
                }
            }

            return $newCurriculum;
        });

        return redirect()
            ->route('catalogs.curricula.show', $curriculum)
            ->with('success', 'Plan de estudios creado correctamente.');
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $curriculum->update($request->validated());

        return back()->with('success', 'Plan de estudios actualizado correctamente.');
    }

    public function toggleActive(Curriculum $curriculum): RedirectResponse
    {
        Gate::authorize('update', $curriculum);

        $curriculum->update([
            'active' => ! $curriculum->active,
        ]);

        $message = $curriculum->active
            ? 'Plan de estudios reactivado correctamente.'
            : 'Plan de estudios desactivado correctamente.';

        return back()->with('success', $message);
    }

    public function updateTermRule(
        UpdateCurriculumTermRuleRequest $request,
        Curriculum $curriculum,
        int $termNumber,
    ): RedirectResponse {
        abort_if($termNumber < 1 || $termNumber > 9, 404);

        CurriculumTermRule::query()->updateOrCreate(
            [
                'curriculum_id' => $curriculum->id,
                'term_number' => $termNumber,
            ],
            [
                'expected_courses' => $request->validated('expected_courses'),
            ],
        );

        return back()->with('success', "Regla del {$termNumber}.º cuatrimestre actualizada.");
    }
}
