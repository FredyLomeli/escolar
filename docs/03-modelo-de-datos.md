# 03 — Modelo de datos

Convenciones: `id` bigint autoincremental, `created_at`/`updated_at` en todas, llaves foráneas con índice, borrado lógico solo donde se indique (`deleted_at`). Los enums se guardan como string y se castean a Enums de PHP.

## Catálogos

**programs** — `code` (único), `name`, `modality_id` → modalities, `active`.
**modalities** — `code` (`regular`, `executive`), `name`.
**curricula** — `program_id`, `version` (string), `valid_from`, `valid_to` null, `active`. Único (`program_id`, `version`).
**courses** — `subject_code` (SUBJ), `course_code` (CRSE), `name`, `active`. **Único (`subject_code`, `course_code`)**.
**curriculum_courses** — `curriculum_id`, `course_id`, `term_number` (1–9), `kind` (`regular|additional|stay`), `default_delivery` (enum de entrega), `weekly_hours` null, `display_name` null. Único (`curriculum_id`, `course_id`).
**curriculum_term_rules** — `curriculum_id`, `term_number`, `expected_courses` (int). Único (`curriculum_id`, `term_number`).

**teachers** — `employee_id` (**ID docente, único**), `full_name`, `email` null, `origin` (`campus|external`), `max_weekly_hours` null, `active`.
**teacher_availabilities** — `teacher_id`, `academic_period_id` null (null = general), `day_of_week` (1–7), `starts_at`, `ends_at`, `type` (`available|unavailable`).
**teacher_courses** — `teacher_id`, `course_id`, `source` (`manual|history`). Único (`teacher_id`, `course_id`). Materias que puede impartir (perfil).

**classrooms** — `code` (único), `name`, `capacity`, `active`.

**shifts** — `modality_id`, `code`, `name`, `active`. Ej.: matutino, vespertino, ejecutivo-sábado, ejecutivo-noche, ejecutivo-entre-semana.
**shift_windows** — `shift_id`, `day_of_week`, `starts_at`, `ends_at`. Horario permitido (advertencia si se sale).
**reserved_blocks** — `shift_id`, `name` (p. ej. "Inglés"), `day_of_week`, `starts_at`, `ends_at`.

**settings** — `key` (único), `value` (json), `description`.

## Periodos y grupos

**academic_periods** — `code` (único), `name`, `year`, `term_of_year` (1–3), `starts_on`, `ends_on`, `weeks`, `status` (`draft|published|closed`).
**student_groups** — `program_id`, `curriculum_id`, `shift_id`, `entry_period_id`, `label` null, `status` (`active|graduated|inactive`).
**group_terms** — `student_group_id`, `academic_period_id`, `term_number` (1–9), `student_count`, `student_count_confirmed` (bool). Único (`student_group_id`, `academic_period_id`).

## Planeación

**sections** — `academic_period_id`, `course_id`, `crn` null (único por periodo cuando existe), `teacher_id` null, `delivery` (`in_person|online_connect|online_live|online_async`), `shift_id`, `start_week`, `end_week`, `students_override` null, `status` (`draft|confirmed`), `notes` null.
**section_groups** — `section_id`, `group_term_id`. Único (`section_id`, `group_term_id`). **Más de un registro = compactación.**
**section_meetings** — `section_id`, `day_of_week` (1–7), `starts_at`, `ends_at`, `classroom_id` null.

Alumnos de una sección = `students_override` si existe, si no la suma de `group_terms.student_count` de sus `section_groups`.

## Importación

**import_batches** — `source_file`, `period_id` null, `status`, `summary` json, `created_by`.
**import_issues** — `import_batch_id`, `severity` (`error|warning|info`), `sheet`, `row`, `message`, `raw` json.

## Usuarios

**users** — del starter kit, más `role` (`admin|coordinator`).

## Índices importantes

- `section_meetings (day_of_week, starts_at, ends_at)` y `(classroom_id)`.
- `sections (academic_period_id, teacher_id)`.
- `group_terms (academic_period_id)`.

## Historial de docentes

No hay tabla aparte: el historial es lo que ya existe en `sections` de periodos `closed`/`published`. El importador crea periodos históricos con sus secciones.
