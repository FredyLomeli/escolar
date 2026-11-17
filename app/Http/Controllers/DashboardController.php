<?php

namespace App\Http\Controllers;

use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Models\AcademicPeriod;
use App\Models\GroupTerm;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $periods = AcademicPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('term_of_year')
            ->orderByDesc('id')
            ->get();

        $requestedPeriodId = $request->integer('period_id');

        $selectedPeriod = $requestedPeriodId > 0
            ? $periods->firstWhere('id', $requestedPeriodId)
            : ($periods->firstWhere('status', AcademicPeriodStatus::Draft) ?? $periods->first());

        $stats = null;
        $recommendations = [];

        if ($selectedPeriod !== null) {
            $totalSections = Section::query()
                ->where('academic_period_id', $selectedPeriod->id)
                ->count();

            $sectionsWithoutTeacher = Section::query()
                ->where('academic_period_id', $selectedPeriod->id)
                ->where('delivery', DeliveryType::InPerson)
                ->whereNull('teacher_id')
                ->count();

            $sectionsWithoutSchedule = Section::query()
                ->where('academic_period_id', $selectedPeriod->id)
                ->where(function ($query): void {
                    $query->whereDoesntHave('meetings')
                        ->orWhereHas('meetings', fn ($meetingQuery) => $meetingQuery->whereNull('classroom_id'));
                })
                ->count();

            $activeGroups = GroupTerm::query()
                ->where('academic_period_id', $selectedPeriod->id)
                ->count();

            $unconfirmedGroups = GroupTerm::query()
                ->where('academic_period_id', $selectedPeriod->id)
                ->where('student_count_confirmed', false)
                ->count();

            $stats = [
                'total_sections' => $totalSections,
                'sections_without_teacher' => $sectionsWithoutTeacher,
                'sections_without_schedule' => $sectionsWithoutSchedule,
                'active_groups' => $activeGroups,
                'unconfirmed_groups' => $unconfirmedGroups,
            ];

            if ($unconfirmedGroups > 0) {
                $recommendations[] = [
                    'tone' => 'warning',
                    'title' => "Confirma el número de alumnos de {$unconfirmedGroups} grupo(s)",
                    'description' => 'La matrícula estimada ayuda a validar correctamente el cupo de las aulas y proponer compactaciones.',
                    'action_label' => 'Revisar grupos',
                    'href' => route('groups.index', ['period_id' => $selectedPeriod->id]),
                ];
            }

            if ($sectionsWithoutTeacher > 0) {
                $recommendations[] = [
                    'tone' => 'warning',
                    'title' => "Hay {$sectionsWithoutTeacher} sección(es) presencial(es) sin docente asignado",
                    'description' => 'Asigna un docente disponible para cada materia presencial del periodo.',
                    'action_label' => 'Ir a planeación',
                    'href' => route('planning.index', ['period_id' => $selectedPeriod->id]),
                ];
            }

            if ($sectionsWithoutSchedule > 0) {
                $recommendations[] = [
                    'tone' => 'warning',
                    'title' => "Hay {$sectionsWithoutSchedule} sección(es) sin horario o sin aula",
                    'description' => 'Completa las sesiones semanales y aulas de cada clase.',
                    'action_label' => 'Ir a planeación',
                    'href' => route('planning.index', ['period_id' => $selectedPeriod->id]),
                ];
            }

            if ($totalSections === 0) {
                $recommendations[] = [
                    'tone' => 'info',
                    'title' => 'Este periodo aún no tiene secciones generadas',
                    'description' => 'Da de alta grupos de primer ingreso o crea un periodo avanzando desde un ciclo anterior.',
                    'action_label' => 'Ir a grupos',
                    'href' => route('groups.index', ['period_id' => $selectedPeriod->id]),
                ];
            }

            if ($recommendations === []) {
                $recommendations[] = [
                    'tone' => 'success',
                    'title' => 'Los datos básicos del periodo están completos',
                    'description' => 'Todos los grupos están confirmados y las secciones presenciales tienen docente, horario y aula.',
                    'action_label' => 'Revisar tablero',
                    'href' => route('planning.index', ['period_id' => $selectedPeriod->id]),
                ];
            }
        }

        return Inertia::render('Dashboard', [
            'periods' => $periods->map(fn (AcademicPeriod $period): array => [
                'id' => $period->id,
                'code' => $period->code,
                'name' => $period->name,
                'year' => $period->year,
                'term_of_year' => $period->term_of_year,
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'weeks' => $period->weeks,
                'status' => $period->status->value,
                'status_label' => $period->status->label(),
            ])->values(),
            'selectedPeriod' => $selectedPeriod ? [
                'id' => $selectedPeriod->id,
                'code' => $selectedPeriod->code,
                'name' => $selectedPeriod->name,
                'year' => $selectedPeriod->year,
                'term_of_year' => $selectedPeriod->term_of_year,
                'starts_on' => $selectedPeriod->starts_on->toDateString(),
                'ends_on' => $selectedPeriod->ends_on->toDateString(),
                'weeks' => $selectedPeriod->weeks,
                'status' => $selectedPeriod->status->value,
                'status_label' => $selectedPeriod->status->label(),
            ] : null,
            'stats' => $stats,
            'recommendations' => $recommendations,
        ]);
    }
}
