<?php

namespace App\Http\Controllers;

use App\Domain\Scheduling\Finding;
use App\Domain\Scheduling\FindingCode;
use App\Domain\Scheduling\FindingSeverity;
use App\Domain\Scheduling\PeriodValidator;
use App\Enums\AcademicPeriodStatus;
use App\Enums\DeliveryType;
use App\Models\AcademicPeriod;
use App\Models\GroupTerm;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PeriodValidator $validator): Response
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
        $alerts = [];

        if ($selectedPeriod !== null) {
            $report = $validator->validate($selectedPeriod);
            $counts = $report->counts();
            $allowPublishWithWarnings = (bool) Setting::getValue('allow_publish_with_warnings', true);
            $canPublish = $report->canPublish($allowPublishWithWarnings);
            $compactionCandidatesCount = count(array_filter(
                $report->findings,
                static fn (Finding $finding): bool => $finding->code === FindingCode::CompactionCandidate,
            ));

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
                ->where('delivery', '!=', DeliveryType::OnlineAsync->value)
                ->where(function ($query): void {
                    $query->whereDoesntHave('meetings')
                        ->orWhere(function ($inPersonQuery): void {
                            $inPersonQuery->where('delivery', DeliveryType::InPerson->value)
                                ->whereHas('meetings', fn ($meetingQuery) => $meetingQuery->whereNull('classroom_id'));
                        });
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
                'errors_count' => $counts['errors'],
                'warnings_count' => $counts['warnings'],
                'infos_count' => $counts['infos'],
                'compaction_candidates_count' => $compactionCandidatesCount,
                'can_publish' => $canPublish,
                'allow_publish_with_warnings' => $allowPublishWithWarnings,
            ];

            $alerts = array_values(array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                array_filter(
                    $report->findings,
                    static fn (Finding $finding): bool => $finding->severity !== FindingSeverity::Info
                        || $finding->code === FindingCode::CompactionCandidate,
                ),
            ));

            if ($counts['errors'] > 0) {
                $recommendations[] = [
                    'tone' => 'error',
                    'title' => "Hay {$counts['errors']} error(es) que impiden publicar el periodo",
                    'description' => 'Resuelve los choques de horario, cupo excedido, bloques reservados o CRN duplicados antes de publicar.',
                    'action_label' => 'Revisar errores',
                    'href' => route('sections.index', ['period_id' => $selectedPeriod->id, 'pending' => 'with_errors']),
                ];
            }

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
                    'action_label' => 'Ir a clases',
                    'href' => route('sections.index', ['period_id' => $selectedPeriod->id, 'pending' => 'without_teacher']),
                ];
            }

            if ($sectionsWithoutSchedule > 0) {
                $recommendations[] = [
                    'tone' => 'warning',
                    'title' => "Hay {$sectionsWithoutSchedule} sección(es) sin horario o sin aula",
                    'description' => 'Completa las sesiones semanales y aulas de cada clase.',
                    'action_label' => 'Ir a clases',
                    'href' => route('sections.index', ['period_id' => $selectedPeriod->id, 'pending' => 'without_schedule']),
                ];
            }

            if ($compactionCandidatesCount > 0) {
                $recommendations[] = [
                    'tone' => 'info',
                    'title' => "Hay {$compactionCandidatesCount} oportunidad(es) de compactación sugerida(s)",
                    'description' => 'Secciones de la misma materia y horario podrían fusionarse para aprovechar mejor aulas y docentes.',
                    'action_label' => 'Ver clases',
                    'href' => route('sections.index', ['period_id' => $selectedPeriod->id]),
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

            if ($recommendations === [] && $counts['warnings'] > 0) {
                $recommendations[] = [
                    'tone' => 'warning',
                    'title' => "El periodo tiene {$counts['warnings']} advertencia(s) por revisar",
                    'description' => $allowPublishWithWarnings
                        ? 'Puedes publicar con advertencias, pero conviene revisarlas en el listado de clases.'
                        : 'La configuración actual requiere atender las advertencias antes de publicar.',
                    'action_label' => 'Revisar advertencias',
                    'href' => route('sections.index', ['period_id' => $selectedPeriod->id, 'pending' => 'with_warnings']),
                ];
            }

            if ($recommendations === []) {
                $recommendations[] = [
                    'tone' => 'success',
                    'title' => 'Planeación sin errores ni advertencias pendientes',
                    'description' => $selectedPeriod->status === AcademicPeriodStatus::Draft
                        ? 'El periodo cumple las validaciones y está listo para publicarse.'
                        : 'Todos los grupos están confirmados y las secciones cumplen las validaciones.',
                    'action_label' => 'Revisar clases',
                    'href' => route('sections.index', ['period_id' => $selectedPeriod->id]),
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
            'alerts' => $alerts,
        ]);
    }
}
