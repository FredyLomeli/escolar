# 02 — Dominio y reglas de negocio

Esta es la fuente de verdad. Todo lo marcado **[POR CONFIRMAR]** debe implementarse como parámetro configurable (ver `08-pendientes.md`).

## 1. Periodos

- Hay **3 periodos por año** (cuatrimestres calendario). Los archivos de ejemplo usan el código `202720` y el texto "Ene – Abr 2027". El formato del código es **[POR CONFIRMAR]**; guárdalo como texto libre único.
- Un periodo tiene estado: `draft` (en planeación), `published` (publicado) y `closed` (histórico).
- Un periodo tiene una duración en **semanas** (se numeran desde 1) y fechas de inicio y fin.

## 2. Carreras, planes y materias

- Una **carrera** (`Program`) tiene un código corto (p. ej. ADME, ARQ, CTCO, DERE, DISGD, MEES, COFI, EDUT; y las ejecutivas EJAD, EJCO, EJDE, EJME, EJVP, EJDER). Las carreras ejecutivas parecen ser versiones de las escolarizadas con prefijo `EJ` **[POR CONFIRMAR]**. Deben poder **registrarse carreras nuevas** en cualquier momento.
- Una carrera tiene uno o más **planes de estudio** (`Curriculum`), versionados. El plan define qué materias van en cada **cuatrimestre del plan (1 a 9)**.
- Una **materia** (`Course`) se identifica por la pareja **SUBJ + CRSE** (en los Excel: columnas `Subject` y `Course`, p. ej. `LMFM` + `HUS02`), más nombre. La pareja es **única globalmente**. La misma materia puede aparecer en planes de varias carreras (tronco común) con distinto nombre comercial; el catálogo guarda un nombre canónico.
- En el plan, cada materia tiene un `kind`: `regular`, `additional` (las 2 adicionales de 1.º y 2.º escolarizado, p. ej. ortografía y razonamiento crítico), `stay` (estadía empresarial, 7.º) y una marca de si es normalmente **en línea**.
- Número esperado de materias por cuatrimestre: **5**, salvo: 1.º y 2.º escolarizado (**+2 adicionales**), 7.º escolarizado (**solo la estadía**), 7.º ejecutivo (**2 o 3 materias + estadía** **[POR CONFIRMAR]**). Se guarda en el plan como regla por cuatrimestre y modalidad, no en el código.

## 3. Modalidades y turnos

| Modalidad | Horario típico | Duración |
|---|---|---|
| Escolarizado matutino | 8:00 a 14:00; inglés por la tarde | 14 semanas |
| Escolarizado vespertino | 14:00 a 18:00/19:00; también con inglés | 14 semanas |
| Ejecutivo | Bloques de 3 a 6 h. Históricamente sábados y noches (19:00–22:00); hoy también entre semana de 10:00 a 13:00 (p. ej. lunes y miércoles) | 3 módulos de 5 semanas |

- Los horarios cambian con frecuencia. Se modelan como **turnos con ventanas horarias configurables** (`Shift` + `ShiftWindow`: día, hora inicio, hora fin). Una sesión de clase puede quedar fuera de la ventana con una **advertencia**, no un error.
- **Escolarizado**: semanas 1–14. Se evalúa cada 5 semanas y las últimas 4 cierran la materia (informativo; no afecta la planeación).
- **Ejecutivo**: módulo 1 = semanas 1–5, módulo 2 = semanas 6–10, módulo 3 = semanas 11–15. En cada módulo se llevan **1 o 2 materias**. Se evalúa al final de cada módulo.

### Regla de tiempo unificada

Toda `Section` tiene `start_week` y `end_week` y una o más sesiones (`SectionMeeting`: día, hora inicio, hora fin, aula). Dos cosas **chocan** si:

- coinciden en **día** y sus **horas se traslapan** (`a.start < b.end AND b.start < a.end`), **y**
- sus **semanas se traslapan** (`a.start_week <= b.end_week AND b.start_week <= a.end_week`), **y**
- ambas pertenecen al **mismo periodo**.

Esta única regla cubre escolarizado y ejecutivo. Una materia de módulo 1 y otra de módulo 2 en el mismo horario **no chocan**.

## 4. Bloques reservados (inglés)

El inglés ocupa un espacio fijo que ninguna otra materia puede tomar, para los grupos del turno. Referencia a confirmar: lunes a jueves 12:30–14:00 y 14:00–15:30 **[POR CONFIRMAR]**. Se modela como `ReservedBlock` configurable por turno (nombre, día, hora inicio, hora fin). La aplicación **no gestiona** el inglés; solo bloquea el horario.

## 5. Materias en línea

- Tipos de entrega (`delivery`): `in_person`, `online_connect` (**Clase Conecta**: normalmente docente de la sede **[POR CONFIRMAR]**), `online_live` (**Vivo Virtual / VV**: normalmente docente externo **[POR CONFIRMAR]**) y `online_async` (**`@`**) **[POR CONFIRMAR]**. `CC` en los Excel parece ser Clase Conecta.
- Una sección en línea puede **no tener docente** y eso **no es error**. Solo se asigna docente si central indica que la imparte la sede.
- Las secciones en línea externas no se validan contra la disponibilidad de docentes de la sede.

## 6. Grupos y avance de periodo

- Un **grupo** (`StudentGroup`) es una generación de una carrera en una modalidad. En cada periodo existe como `GroupTerm` con su cuatrimestre (`term_number`) y número de alumnos.
- **Avanzar de periodo**: por cada grupo activo se crea su `GroupTerm` del periodo siguiente con `term_number + 1` y el último número de alumnos como estimación editable. Al pasar del 9 el grupo se marca `graduated`. Los grupos que se ven al mismo tiempo suelen estar separados por varios cuatrimestres (en los ejemplos: 2.º, 5.º y 8.º).
- Después se generan las **secciones** del periodo a partir de las materias del plan para ese cuatrimestre, con el último docente que impartió esa materia como sugerencia.
- El coordinador puede además dar de alta grupos nuevos (primer cuatrimestre).

## 7. Compactación (mezcla de grupos)

- Una **compactación** es una sección que atiende a **varios `GroupTerm`**. Ocurre sobre todo en cuatrimestres iniciales con materias de **tronco común** (misma pareja SUBJ + CRSE en varias carreras).
- Condiciones: misma materia (SUBJ + CRSE), mismo periodo, misma modalidad y **mismo horario** (lo garantiza ser una sola sección). Un solo **CRN** para la sección.
- Alumnos de la sección = suma de los grupos. Esa suma se valida contra la capacidad del aula. El docente cuenta una sola vez.
- Tamaño objetivo por sección: **~20 alumnos** (parámetro). Algo por debajo o por encima tras compactar es aceptable; solo hay advertencia suave. No hay mínimo ni máximo duro aparte de la capacidad del aula.
- La decisión es del coordinador; el sistema **sugiere** candidatos y permite **descompactar**.
- Fuera de v1: compactar ejecutivo con escolarizado.

## 8. Docentes y aulas

- **Docente**: se identifica únicamente por su **ID docente** (único). El nombre es informativo y puede tener variantes en los datos de origen. Un docente puede impartir en **varios turnos y modalidades**: los choques se validan en todos.
- Un docente tiene **disponibilidad** por día y hora (opcional) y un historial de materias impartidas.
- **Aula**: código, nombre con el que se conoce y **capacidad**. En los Excel aparece como número (`101`, `204`) o clave (`CTC 01`).

## 9. Parámetros configurables (tabla `settings`)

`target_section_size` (20), `section_size_soft_min`, `section_size_soft_max`, `weeks_in_term_regular` (14), `weeks_in_module_executive` (5), `modules_per_term_executive` (3), `proposal_history_periods` (cuántos periodos atrás considerar).
