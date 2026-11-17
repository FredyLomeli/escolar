# 06 — Plan de implementación por fases

Trabaja **una fase a la vez**. Al terminar cada una: pruebas en verde, Pint limpio y un resumen (hecho / pendiente / supuestos). Espera la revisión del usuario antes de pasar a la siguiente.

## Fase 0 — Cimientos

- El proyecto **Laravel 13** con el starter kit de Vue + Inertia **ya está creado**. Verifica que corra en Docker con Sail (MySQL), con Pest y Pint, y que cumpla PHP 8.3 o superior.
- Instala las dependencias de `docs/09-dependencias.md` (Excel, PDF, respaldos, Boost y Larastan) y configura Larastan en nivel 6 o más.
- Zona horaria `America/Mexico_City`, idioma `es`, textos de interfaz en español.
- Roles `admin` y `coordinator`, usuarios semilla, políticas base.
- Layout principal con la navegación de `05-pantallas.md`.
- `README.md` con cómo levantar el proyecto (`./vendor/bin/sail up`), correr pruebas y sembrar datos.

**Aceptación:** inicia sesión con ambos roles; `sail test` pasa; la navegación muestra las secciones aunque estén vacías.

## Fase 1 — Catálogos

- Migraciones, modelos, factories y seeders de: modalidades, carreras, planes, materias, malla (`curriculum_courses`), reglas por cuatrimestre, docentes (con perfil y disponibilidad), aulas, turnos, ventanas, bloques reservados y `settings`.
- CRUD en Inertia de todos los catálogos según `05-pantallas.md`, incluida la **malla editable por cuatrimestre (1–9)**.
- Restricciones únicas: SUBJ+CRSE, ID docente, código de aula.
- No borrar entidades con historial; solo desactivar.

**Aceptación:** se puede registrar una carrera nueva con su plan completo y un docente con disponibilidad, solo desde la interfaz.

## Fase 2 — Periodos, grupos y secciones

- `academic_periods`, `student_groups`, `group_terms`, `sections`, `section_groups`, `section_meetings`.
- Asistente **Crear siguiente periodo** (avance de grupos, graduados, generación de secciones desde el plan, copiar sesiones opcional).
- Pantalla de grupos con edición de alumnos.
- Edición de secciones (docente, aula, sesiones, semanas, entrega).

**Aceptación:** a partir de un periodo con 3 grupos, el asistente crea el siguiente con cuatrimestre +1 y las secciones correctas según el plan; un grupo en 9.º queda graduado.

## Fase 3 — Motor de validación

- Implementar **todas** las reglas de `04-validaciones-y-propuestas.md` como clases puras, con pruebas de caso correcto e incorrecto cada una. Cubrir especialmente: choque entre turnos, semanas que no se traslapan (módulo 1 vs módulo 2 no chocan), compactación (cuenta una vez al docente, suma alumnos) y secciones en línea sin docente.
- Endpoint que devuelve los hallazgos de un periodo y los afectados por un cambio.
- Bloqueo de publicación con errores.

**Aceptación:** una batería de pruebas con escenarios armados reproduce cada código de hallazgo; publicar un periodo con errores falla con mensaje claro.

## Fase 4 — Tablero de planeación

- Cuadrícula semanal por grupo, docente y aula, con bloques reservados y filtro de módulo para ejecutivo.
- Panel lateral de sección, panel de alertas con navegación, revalidación en vivo.
- Compactar y descompactar secciones.

**Aceptación:** el usuario asigna un docente y un aula, provoca un choque y lo ve en rojo; lo corrige y la alerta desaparece sin recargar la página.

## Fase 5 — Importador

- Importador de `Materias-Maestro` descrito en `07-importador-excel.md`, con vista previa, reporte de problemas, idempotencia y creación de historial.
- Seeders de demostración con nombres ficticios.

**Aceptación:** importar los tres libros de ejemplo genera el reporte esperado (variantes de nombre, marcadores, IDs sin cero) y el historial queda disponible para las propuestas.

## Hito de Carga de Datos Oficiales (sin bloquear Fases 1–4)

- Las **Fases 1 a 4** pueden desarrollarse y validarse completamente con catálogos y datos de ejemplo configurables (sin detener el avance mientras la universidad entrega la información oficial).
- Antes de ejecutar en firme la **Fase 6 (Propuestas)** y cerrar la **Fase 7**, se realiza un **corte oficial** para sustituir los datos de demostración por los datos reales:
  1. Confirmar los puntos pendientes de `docs/08-pendientes.md` (bloques de inglés, ventanas ejecutivas, significados de `@`/`VV`/`CC`, reglas de estadía).
  2. Cargar el catálogo oficial de **aulas y capacidades**, **carreras**, **planes de estudio** y **materias vigentes** (mediante un `OfficialCatalogSeeder` limpio o captura en interfaz).
  3. Colocar los tres libros reales de Excel en `docs/samples/` (manteniendo el repositorio privado) e importarlos con el asistente de la Fase 5 para poblar **grupos vigentes**, **docentes** e **historial**.

**Aceptación del hito:** base de datos sin registros ficticios de prueba; aulas, planes vigentes, grupos actuales e historial real listos para validar las propuestas de la Fase 6 y las exportaciones de la Fase 7.

## Fase 6 — Propuestas

- Propuesta de docente (top 3 con explicación) y sugerencia de compactación según `04-validaciones-y-propuestas.md`.
- Integración en el panel lateral y en el panel de compactaciones sugeridas.

**Aceptación:** con historial importado, una sección nueva sin docente muestra 3 candidatos ordenados con su motivo y ninguno con conflicto de horario.

## Fase 7 — Exportación y pulido

- Exportar horarios por carrera/grupo, docente y aula a Excel y PDF.
- Estados vacíos, mensajes de ayuda, accesibilidad básica, rendimiento (sin consultas N+1 en el tablero) y documentación de usuario breve en español.

**Aceptación:** el horario final de un periodo se descarga en Excel y PDF legibles; el tablero de un periodo con ~60 secciones carga en menos de 2 segundos en el entorno local.

---

## Cómo usar este plan con el agente

Para cada fase, pega algo como:

> Lee `AGENTS.md` y `docs/`. Vamos a hacer la **Fase N** de `docs/06-plan-de-implementacion.md`. Primero muéstrame el plan (migraciones, archivos, pruebas). Después impleméntalo, corre las pruebas y resume qué hiciste, qué supusiste y qué quedó pendiente.

Pide siempre que **ejecute las pruebas y Pint** antes de decir que terminó, y revisa el resumen de supuestos contra `08-pendientes.md`.
