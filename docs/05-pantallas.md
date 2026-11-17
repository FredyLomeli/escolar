# 05 — Pantallas y experiencia de uso

Principio: el usuario **no conoce el proceso**. Cada pantalla debe decir qué falta, qué está mal y cuál es el siguiente paso. Lenguaje claro, sin jerga técnica. Interfaz en español.

## Navegación

Inicio · Planeación · Grupos · Catálogos (Carreras y planes, Materias, Docentes, Aulas, Turnos y bloques, Parámetros) · Importar · Usuarios (solo admin).

## 1. Inicio (panel del periodo)

- Selector de periodo y su estado.
- Tarjetas: secciones totales, sin docente, sin aula, errores, advertencias, compactaciones sugeridas.
- Lista de **"Siguiente paso recomendado"** (p. ej. "Confirma el número de alumnos de 6 grupos", "Hay 4 choques de docente").
- Botón **"Crear siguiente periodo"** (asistente).

## 2. Asistente "Crear siguiente periodo"

1. Elegir periodo origen y datos del nuevo (código, fechas, semanas).
2. Revisar grupos que avanzan (cuatrimestre nuevo, graduados). Editar alumnos estimados.
3. Opciones: copiar sesiones del periodo anterior sí/no; sugerir docentes sí/no.
4. Resumen y creación. Termina en el tablero de planeación.

## 3. Tablero de planeación (pantalla central)

- Tres vistas con selector: **por grupo**, **por docente**, **por aula**. Cuadrícula semanal (lunes a sábado) con bloques de sesión coloreados por turno.
- Muestra los **bloques reservados** (inglés) como zonas atenuadas no editables.
- Para ejecutivo, un control de **módulo** (1: semanas 1–5, 2: 6–10, 3: 11–15) que filtra qué se ve.
- Clic en una sesión abre un **panel lateral** de la sección: materia, grupos (con chips si es compactada), docente con selector que muestra **propuestas con su motivo**, aula con selector que muestra capacidad, sesiones editables, alumnos, CRN.
- **Panel de alertas** fijo: errores en rojo, advertencias en ámbar, cada una con un botón "Ver" que lleva a la sección.
- Cambios guardan y revalidan al instante; la interfaz actualiza solo lo afectado.
- Acciones por sección: asignar docente, asignar aula, mover sesión, **compactar con…**, **descompactar**, duplicar, eliminar.
- Panel **"Compactaciones sugeridas"** con aceptar/descartar.
- Accesible con teclado en lo básico (foco visible, etiquetas en controles). No es necesario arrastrar y soltar en la v1; los selectores bastan.

## 4. Catálogos

CRUD con búsqueda, filtros, paginación y confirmación al desactivar (no borrar si tiene historial).

- **Carreras y planes**: crear carrera, crear versión de plan, y una **malla editable** por cuatrimestre (1 a 9) para agregar materias, marcar tipo (regular/adicional/estadía), entrega por defecto, horas semanales, y reglas de número de materias por cuatrimestre.
- **Materias**: SUBJ + CRSE + nombre, con aviso si ya existe la pareja.
- **Docentes**: ID docente (único), nombre, origen, perfil de materias, **disponibilidad** (cuadrícula semanal para marcar disponible/no disponible).
- **Aulas**: código, nombre, capacidad.
- **Turnos y bloques**: turnos, sus ventanas horarias y bloques reservados (inglés).
- **Parámetros**: `settings` con descripción de cada uno.

## 5. Grupos

Lista de grupos por periodo con carrera, cuatrimestre, turno, alumnos y marca de "alumnos confirmados". Edición masiva del número de alumnos.

## 6. Importar

Asistente de carga del Excel histórico: subir archivo → vista previa y **reporte de problemas** (docentes con nombre distinto, marcadores, filas sin docente) → mapeo de decisiones → confirmar. Ver `07-importador-excel.md`.

## 7. Exportar

Del tablero: horario por carrera/grupo, por docente y por aula, en **Excel** y **PDF** imprimible. Las exportaciones nunca muestran propuestas ni alertas internas.

## Estados vacíos y errores

Cada lista y tablero tiene un estado vacío explicativo con la acción que sigue. Los errores de validación se muestran junto al campo, en español.
