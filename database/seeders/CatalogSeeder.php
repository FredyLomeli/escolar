<?php

namespace Database\Seeders;

use App\Enums\AvailabilityType;
use App\Enums\CourseKind;
use App\Enums\DeliveryType;
use App\Enums\ModalityCode;
use App\Enums\TeacherCourseSource;
use App\Enums\TeacherOrigin;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\CurriculumTermRule;
use App\Models\Modality;
use App\Models\Program;
use App\Models\ReservedBlock;
use App\Models\Shift;
use App\Models\ShiftWindow;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherCourse;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $regularModality = Modality::query()->firstOrCreate(
            ['code' => ModalityCode::Regular],
            ['name' => ModalityCode::Regular->label()],
        );

        $executiveModality = Modality::query()->firstOrCreate(
            ['code' => ModalityCode::Executive],
            ['name' => ModalityCode::Executive->label()],
        );

        $this->seedShifts($regularModality, $executiveModality);
        $this->seedClassrooms();
        $courses = $this->seedCourses();
        $this->seedProgramsAndCurricula($regularModality, $executiveModality, $courses);
        $this->seedTeachers($courses);
    }

    private function seedShifts(Modality $regularModality, Modality $executiveModality): void
    {
        $matutino = Shift::query()->firstOrCreate(
            ['code' => 'matutino'],
            ['modality_id' => $regularModality->id, 'name' => 'Escolarizado Matutino', 'active' => true],
        );

        foreach (range(1, 5) as $day) {
            ShiftWindow::query()->firstOrCreate([
                'shift_id' => $matutino->id,
                'day_of_week' => $day,
                'starts_at' => '08:00:00',
                'ends_at' => '14:00:00',
            ]);
        }

        foreach (range(1, 4) as $day) {
            ReservedBlock::query()->firstOrCreate([
                'shift_id' => $matutino->id,
                'name' => 'Inglés',
                'day_of_week' => $day,
                'starts_at' => '12:30:00',
                'ends_at' => '14:00:00',
            ]);
        }

        $vespertino = Shift::query()->firstOrCreate(
            ['code' => 'vespertino'],
            ['modality_id' => $regularModality->id, 'name' => 'Escolarizado Vespertino', 'active' => true],
        );

        foreach (range(1, 5) as $day) {
            ShiftWindow::query()->firstOrCreate([
                'shift_id' => $vespertino->id,
                'day_of_week' => $day,
                'starts_at' => '14:00:00',
                'ends_at' => '19:00:00',
            ]);
        }

        foreach (range(1, 4) as $day) {
            ReservedBlock::query()->firstOrCreate([
                'shift_id' => $vespertino->id,
                'name' => 'Inglés',
                'day_of_week' => $day,
                'starts_at' => '14:00:00',
                'ends_at' => '15:30:00',
            ]);
        }

        $sabado = Shift::query()->firstOrCreate(
            ['code' => 'ejecutivo-sabado'],
            ['modality_id' => $executiveModality->id, 'name' => 'Ejecutivo Sábado', 'active' => true],
        );
        ShiftWindow::query()->firstOrCreate([
            'shift_id' => $sabado->id,
            'day_of_week' => 6,
            'starts_at' => '08:00:00',
            'ends_at' => '18:00:00',
        ]);

        $noche = Shift::query()->firstOrCreate(
            ['code' => 'ejecutivo-noche'],
            ['modality_id' => $executiveModality->id, 'name' => 'Ejecutivo Noche', 'active' => true],
        );
        foreach (range(1, 5) as $day) {
            ShiftWindow::query()->firstOrCreate([
                'shift_id' => $noche->id,
                'day_of_week' => $day,
                'starts_at' => '19:00:00',
                'ends_at' => '22:00:00',
            ]);
        }

        $entreSemana = Shift::query()->firstOrCreate(
            ['code' => 'ejecutivo-entre-semana'],
            ['modality_id' => $executiveModality->id, 'name' => 'Ejecutivo Entre Semana', 'active' => true],
        );
        foreach (range(1, 5) as $day) {
            ShiftWindow::query()->firstOrCreate([
                'shift_id' => $entreSemana->id,
                'day_of_week' => $day,
                'starts_at' => '10:00:00',
                'ends_at' => '13:00:00',
            ]);
        }
    }

    private function seedClassrooms(): void
    {
        $classrooms = [
            ['code' => '101', 'name' => 'Aula 101', 'capacity' => 25],
            ['code' => '102', 'name' => 'Aula 102', 'capacity' => 25],
            ['code' => '201', 'name' => 'Aula 201', 'capacity' => 30],
            ['code' => '202', 'name' => 'Aula 202', 'capacity' => 30],
            ['code' => '204', 'name' => 'Aula 204', 'capacity' => 20],
            ['code' => '301', 'name' => 'Aula 301', 'capacity' => 35],
            ['code' => 'CTC 01', 'name' => 'Centro de Cómputo 01', 'capacity' => 15],
            ['code' => 'MAGNA', 'name' => 'Aula Magna', 'capacity' => 40],
        ];

        foreach ($classrooms as $classroom) {
            Classroom::query()->firstOrCreate(
                ['code' => $classroom['code']],
                [
                    'name' => $classroom['name'],
                    'capacity' => $classroom['capacity'],
                    'active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, Course>
     */
    private function seedCourses(): array
    {
        $catalog = [
            ['subject' => 'LMFM', 'course' => 'HUS01', 'name' => 'Ortografía y Redacción'],
            ['subject' => 'LMFM', 'course' => 'HUS02', 'name' => 'Razonamiento Crítico'],
            ['subject' => 'ADME', 'course' => 'ADM11', 'name' => 'Fundamentos de Administración'],
            ['subject' => 'ADME', 'course' => 'CNT12', 'name' => 'Contabilidad General'],
            ['subject' => 'ADME', 'course' => 'ECO13', 'name' => 'Microeconomía'],
            ['subject' => 'TRCO', 'course' => 'COM14', 'name' => 'Comunicación Oral y Escrita'],
            ['subject' => 'TRCO', 'course' => 'MET15', 'name' => 'Metodología de la Investigación'],
            ['subject' => 'ADME', 'course' => 'MKT21', 'name' => 'Mercadotecnia Estratégica'],
            ['subject' => 'ADME', 'course' => 'FIN22', 'name' => 'Matemáticas Financieras'],
            ['subject' => 'TRCO', 'course' => 'DER23', 'name' => 'Marco Legal de los Negocios'],
            ['subject' => 'ADME', 'course' => 'RHU31', 'name' => 'Gestión del Talento Humano'],
            ['subject' => 'ADME', 'course' => 'OPE41', 'name' => 'Administración de Operaciones'],
            ['subject' => 'ADME', 'course' => 'EST51', 'name' => 'Estadística Aplicada'],
            ['subject' => 'ADME', 'course' => 'DIR61', 'name' => 'Dirección Empresarial'],
            ['subject' => 'ESTD', 'course' => 'EST70', 'name' => 'Estadía Empresarial'],
        ];

        $indexed = [];

        foreach ($catalog as $row) {
            $course = Course::query()->firstOrCreate(
                [
                    'subject_code' => $row['subject'],
                    'course_code' => $row['course'],
                ],
                [
                    'name' => $row['name'],
                    'active' => true,
                ],
            );

            $indexed["{$row['subject']}-{$row['course']}"] = $course;
        }

        return $indexed;
    }

    /**
     * @param  array<string, Course>  $courses
     */
    private function seedProgramsAndCurricula(
        Modality $regularModality,
        Modality $executiveModality,
        array $courses,
    ): void {
        $adme = Program::query()->firstOrCreate(
            ['code' => 'ADME'],
            [
                'name' => 'Licenciatura en Administración de Empresas',
                'modality_id' => $regularModality->id,
                'active' => true,
            ],
        );

        $admeCurriculum = Curriculum::query()->firstOrCreate(
            ['program_id' => $adme->id, 'version' => '2026'],
            [
                'valid_from' => '2026-01-01',
                'valid_to' => null,
                'active' => true,
            ],
        );

        $regularTermRules = [
            1 => 7,
            2 => 7,
            3 => 5,
            4 => 5,
            5 => 5,
            6 => 5,
            7 => 1,
            8 => 5,
            9 => 5,
        ];

        foreach ($regularTermRules as $term => $expected) {
            CurriculumTermRule::query()->firstOrCreate(
                ['curriculum_id' => $admeCurriculum->id, 'term_number' => $term],
                ['expected_courses' => $expected],
            );
        }

        $admeCourses = [
            ['key' => 'LMFM-HUS01', 'term' => 1, 'kind' => CourseKind::Additional, 'delivery' => DeliveryType::OnlineConnect, 'hours' => 2.0],
            ['key' => 'LMFM-HUS02', 'term' => 2, 'kind' => CourseKind::Additional, 'delivery' => DeliveryType::OnlineConnect, 'hours' => 2.0],
            ['key' => 'ADME-ADM11', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ADME-CNT12', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ADME-ECO13', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'TRCO-COM14', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 3.0],
            ['key' => 'TRCO-MET15', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::OnlineAsync, 'hours' => 3.0],
            ['key' => 'ADME-MKT21', 'term' => 2, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ADME-FIN22', 'term' => 2, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'TRCO-DER23', 'term' => 2, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::OnlineLive, 'hours' => 3.0],
            ['key' => 'ADME-RHU31', 'term' => 3, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ADME-OPE41', 'term' => 4, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ADME-EST51', 'term' => 5, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ADME-DIR61', 'term' => 6, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 4.0],
            ['key' => 'ESTD-EST70', 'term' => 7, 'kind' => CourseKind::Stay, 'delivery' => DeliveryType::InPerson, 'hours' => 10.0],
        ];

        foreach ($admeCourses as $item) {
            CurriculumCourse::query()->firstOrCreate(
                [
                    'curriculum_id' => $admeCurriculum->id,
                    'course_id' => $courses[$item['key']]->id,
                ],
                [
                    'term_number' => $item['term'],
                    'kind' => $item['kind'],
                    'default_delivery' => $item['delivery'],
                    'weekly_hours' => $item['hours'],
                ],
            );
        }

        $ejad = Program::query()->firstOrCreate(
            ['code' => 'EJAD'],
            [
                'name' => 'Licenciatura en Administración de Empresas (Ejecutivo)',
                'modality_id' => $executiveModality->id,
                'active' => true,
            ],
        );

        $ejadCurriculum = Curriculum::query()->firstOrCreate(
            ['program_id' => $ejad->id, 'version' => '2026'],
            [
                'valid_from' => '2026-01-01',
                'valid_to' => null,
                'active' => true,
            ],
        );

        $executiveTermRules = [
            1 => 5,
            2 => 5,
            3 => 5,
            4 => 5,
            5 => 5,
            6 => 5,
            7 => 4,
            8 => 5,
            9 => 5,
        ];

        foreach ($executiveTermRules as $term => $expected) {
            CurriculumTermRule::query()->firstOrCreate(
                ['curriculum_id' => $ejadCurriculum->id, 'term_number' => $term],
                ['expected_courses' => $expected],
            );
        }

        $ejadCourses = [
            ['key' => 'ADME-ADM11', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 3.0],
            ['key' => 'ADME-CNT12', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 3.0],
            ['key' => 'TRCO-COM14', 'term' => 1, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 3.0],
            ['key' => 'ADME-MKT21', 'term' => 2, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 3.0],
            ['key' => 'ADME-FIN22', 'term' => 2, 'kind' => CourseKind::Regular, 'delivery' => DeliveryType::InPerson, 'hours' => 3.0],
            ['key' => 'ESTD-EST70', 'term' => 7, 'kind' => CourseKind::Stay, 'delivery' => DeliveryType::InPerson, 'hours' => 6.0],
        ];

        foreach ($ejadCourses as $item) {
            CurriculumCourse::query()->firstOrCreate(
                [
                    'curriculum_id' => $ejadCurriculum->id,
                    'course_id' => $courses[$item['key']]->id,
                ],
                [
                    'term_number' => $item['term'],
                    'kind' => $item['kind'],
                    'default_delivery' => $item['delivery'],
                    'weekly_hours' => $item['hours'],
                ],
            );
        }
    }

    /**
     * @param  array<string, Course>  $courses
     */
    private function seedTeachers(array $courses): void
    {
        $teachers = [
            ['id' => '00100001', 'name' => 'Mtra. Sofía Mendoza Ríos', 'origin' => TeacherOrigin::Campus, 'hours' => 24, 'courses' => ['ADME-ADM11', 'ADME-DIR61']],
            ['id' => '00100002', 'name' => 'Dr. Alejandro Vega Salinas', 'origin' => TeacherOrigin::Campus, 'hours' => 20, 'courses' => ['ADME-CNT12', 'ADME-FIN22']],
            ['id' => '00100003', 'name' => 'Mtra. Valeria Navarro Cruz', 'origin' => TeacherOrigin::Campus, 'hours' => 24, 'courses' => ['TRCO-COM14', 'LMFM-HUS01']],
            ['id' => '00100004', 'name' => 'Mtro. Ricardo Paredes Luna', 'origin' => TeacherOrigin::Campus, 'hours' => 18, 'courses' => ['ADME-ECO13', 'ADME-EST51']],
            ['id' => '00100005', 'name' => 'Lic. Mariana Ortega Domínguez', 'origin' => TeacherOrigin::Campus, 'hours' => 20, 'courses' => ['ADME-MKT21', 'ADME-RHU31']],
            ['id' => '00100006', 'name' => 'Ing. Fernando Ibarra Soto', 'origin' => TeacherOrigin::Campus, 'hours' => 16, 'courses' => ['ADME-OPE41', 'ADME-EST51']],
            ['id' => '00100007', 'name' => 'Mtra. Daniela Morales Peña', 'origin' => TeacherOrigin::Campus, 'hours' => 22, 'courses' => ['LMFM-HUS02', 'TRCO-MET15']],
            ['id' => '00100008', 'name' => 'Lic. Héctor Campos Vázquez', 'origin' => TeacherOrigin::External, 'hours' => 12, 'courses' => ['TRCO-DER23']],
            ['id' => '00100009', 'name' => 'Mtro. Gabriel Castillo Herrera', 'origin' => TeacherOrigin::Campus, 'hours' => 20, 'courses' => ['ESTD-EST70', 'ADME-ADM11']],
            ['id' => '00100010', 'name' => 'Dra. Claudia Jiménez Reyes', 'origin' => TeacherOrigin::External, 'hours' => 15, 'courses' => ['ADME-FIN22', 'ADME-DIR61']],
        ];

        foreach ($teachers as $index => $row) {
            $teacher = Teacher::query()->firstOrCreate(
                ['employee_id' => $row['id']],
                [
                    'full_name' => $row['name'],
                    'email' => "docente{$row['id']}@ejemplo.test",
                    'origin' => $row['origin'],
                    'max_weekly_hours' => $row['hours'],
                    'active' => true,
                ],
            );

            foreach ($row['courses'] as $courseKey) {
                TeacherCourse::query()->firstOrCreate(
                    [
                        'teacher_id' => $teacher->id,
                        'course_id' => $courses[$courseKey]->id,
                    ],
                    ['source' => TeacherCourseSource::Manual],
                );
            }

            // Disponibilidad base de ejemplo en días hábiles para la primera mitad y sábados para algunos docentes
            if ($index < 7) {
                foreach ([1, 3, 5] as $day) {
                    TeacherAvailability::query()->firstOrCreate([
                        'teacher_id' => $teacher->id,
                        'academic_period_id' => null,
                        'day_of_week' => $day,
                        'starts_at' => '08:00:00',
                        'ends_at' => '14:00:00',
                        'type' => AvailabilityType::Available,
                    ]);
                }
            } else {
                TeacherAvailability::query()->firstOrCreate([
                    'teacher_id' => $teacher->id,
                    'academic_period_id' => null,
                    'day_of_week' => 6,
                    'starts_at' => '08:00:00',
                    'ends_at' => '18:00:00',
                    'type' => AvailabilityType::Available,
                ]);
            }
        }
    }
}
