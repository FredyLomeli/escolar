# 01 — Visión y alcance

## Problema

Cada cuatrimestre (tres veces al año) el coordinador académico debe:

1. Validar qué docentes tiene disponibles y sus perfiles.
2. Asignarles materias del total ofertado para el periodo.
3. Asignar aulas, cuidando la capacidad máxima contra el número de alumnos.
4. Detectar choques y ajustar.

Hoy se hace en **hojas de Excel** (un archivo por turno, una hoja por carrera, formato libre). La persona que dominaba el proceso ya no está; quien lo adopta no lo conoce. Los archivos actuales tienen nombres de docentes escritos distinto, marcadores informales (`@`, `VV`, `CC`), celdas vacías y errores de copia (año equivocado).

## Objetivo

Una aplicación en la que el coordinador **revise y ajuste** una propuesta de planeación en lugar de construirla desde cero, con validación inmediata de docentes, aulas y capacidades.

## Usuarios

- **Coordinador académico** (principal): planea y valida.
- **Administrador**: mantiene catálogos y usuarios (puede ser la misma persona al inicio).

## Alcance de la v1

Incluido:

- Catálogos: carreras, planes de estudio, materias, docentes, aulas, modalidades/turnos, bloques reservados, parámetros.
- Registro de **nuevas carreras y planes** sin tocar código.
- Periodos y **avance de periodo**: los mismos grupos pasan al siguiente cuatrimestre.
- Secciones (clases) con docente, aula y horario; **compactación** de grupos.
- Validaciones de choque (docente, aula, grupo), capacidad y disponibilidad.
- Propuesta de docente basada en el historial.
- Tablero de planeación (vistas por grupo, docente y aula) con alertas.
- Importación única del historial desde los Excel actuales.
- Exportación del horario final.

Fuera de la v1 (documentado, no implementar):

- Mezcla de alumnos de ejecutivo con escolarizado (revalidación entre materias con distinta clave).
- Alumnos que cargan un CRN de otro horario para repetir materia.
- Captura de calificaciones, inscripción de alumnos individuales, nómina o contratación.
- Optimizador matemático global (la propuesta es heurística y explicable).
- Integraciones con sistemas institucionales.

## Criterios de éxito

- Un usuario nuevo puede crear el siguiente periodo a partir del anterior y llegar a una planeación sin choques siguiendo solo las alertas.
- Ningún docente ni aula queda doble reservado al publicar un periodo.
- Cambiar la oferta de horarios (por ejemplo, un nuevo turno ejecutivo) se hace desde configuración, sin modificar código.
