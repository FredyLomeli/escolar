# 04 — Validaciones y propuestas

Toda la lógica vive en `app/Domain/Scheduling/...` como clases puras (sin HTTP) con pruebas unitarias. El motor recibe un periodo y devuelve una lista de **hallazgos**.

## Formato de hallazgo

`code`, `severity` (`error|warning|info`), `message` (español, accionable), `section_ids[]`, `entity` (docente/aula/grupo), `suggestion` null.

## Primitivas

- `weeksOverlap(a, b)`: `a.start_week <= b.end_week && b.start_week <= a.end_week`.
- `timeOverlap(a, b)`: mismo `day_of_week` y `a.starts_at < b.ends_at && b.starts_at < a.ends_at`.
- Dos sesiones **chocan** si ambas se cumplen y son del mismo periodo.

## Errores (impiden publicar el periodo)

| Código | Regla |
|---|---|
| `TEACHER_CONFLICT` | Un docente con dos sesiones que chocan, **en cualquier turno o modalidad**. Una sección compactada cuenta una sola vez. |
| `CLASSROOM_CONFLICT` | Un aula con dos sesiones que chocan. |
| `GROUP_CONFLICT` | Un `GroupTerm` con dos secciones distintas que chocan. |
| `CAPACITY_EXCEEDED` | Alumnos de la sección > capacidad del aula asignada. |
| `RESERVED_BLOCK_CONFLICT` | Una sesión cae sobre un bloque reservado (inglés) del turno de alguno de sus grupos. |
| `TEACHER_UNAVAILABLE` | La sesión cae en un horario marcado `unavailable`, o fuera de todas las franjas `available` cuando el docente tiene disponibilidad declarada. |
| `DUPLICATE_CRN` | Dos secciones del periodo con el mismo CRN. |
| `COMPACTION_INVALID` | Una sección con varios grupos cuyos grupos tienen distinta modalidad o la materia no coincide con el plan de alguno. |

## Advertencias (no impiden publicar)

| Código | Regla |
|---|---|
| `OUTSIDE_SHIFT_WINDOW` | La sesión sale de las ventanas del turno. |
| `NO_AVAILABILITY_DATA` | El docente no tiene disponibilidad registrada (no se puede validar). |
| `TEACHER_NOT_ELIGIBLE` | El docente no tiene la materia en su perfil ni en su historial. |
| `TEACHER_OVERLOAD` | Horas semanales del docente > `max_weekly_hours`. |
| `SECTION_SIZE_LOW` / `SECTION_SIZE_HIGH` | Alumnos fuera del rango suave alrededor de `target_section_size` (~20). |
| `GROUP_COURSE_COUNT` | El grupo tiene distinto número de materias que `expected_courses` de su plan y cuatrimestre. |
| `SECTION_WITHOUT_TEACHER` | Sección presencial sin docente. **No aplica** a secciones en línea. |
| `SECTION_WITHOUT_SCHEDULE` | Sección sin sesiones, o sin aula. |
| `HOURS_MISMATCH` | Horas semanales sumadas ≠ `weekly_hours` de la materia (si está definido). |

## Información

- `ONLINE_NO_TEACHER`: sección en línea sin docente (esperado).
- `COMPACTION_CANDIDATE`: ver abajo.

## Comportamiento

- Se **permite guardar** secciones con errores mientras el periodo esté en `draft` (el coordinador necesita trabajar iterativamente); los errores se muestran en vivo. **Publicar** está bloqueado mientras existan errores.
- La validación se ejecuta en el backend. Al editar una sección, el endpoint devuelve los hallazgos afectados para que la interfaz los refresque sin recargar.

## Propuesta de docente

Para cada sección sin docente (presencial, o en línea de sede), calcular un puntaje por docente candidato y devolver los **3 mejores con explicación**:

1. Descartar a quien tenga `TEACHER_CONFLICT` o `TEACHER_UNAVAILABLE` con las sesiones de la sección (si ya tiene horario).
2. Puntaje base: veces que impartió esa materia en los últimos `proposal_history_periods` periodos, con más peso al periodo más reciente.
3. Bonus si la materia está en su perfil manual (`teacher_courses`).
4. Penalización suave por carga actual cercana a `max_weekly_hours` o alta carga relativa.
5. Empate: menor carga actual.

Cada propuesta explica el motivo ("Impartió esta materia en los 2 periodos anteriores; disponible; carga 12 h").

## Sugerencia de compactación

Para secciones del mismo periodo que cumplan: misma materia (SUBJ + CRSE), misma modalidad, **mismas sesiones (día y hora)**, y con suma de alumnos ≤ capacidad de algún aula libre en ese horario: sugerir juntarlas, indicando alumnos resultantes y su distancia a `target_section_size`. Prioriza combinaciones que se acerquen más a ~20. El coordinador acepta o descarta; aceptar fusiona las secciones en una (un CRN) y **descompactar** las separa.

## Generación de secciones al avanzar de periodo

Para cada `GroupTerm` nuevo: crear una `Section` por cada materia del plan en ese `term_number` (más adicionales/estadía según reglas), con `delivery` = `default_delivery`, `shift_id` del grupo, semanas según modalidad, y docente sugerido = el último que la impartió. Opción de **copiar sesiones** del periodo anterior para el mismo grupo y materia. Las materias comunes a varios grupos se generan por separado; la compactación se decide después.
