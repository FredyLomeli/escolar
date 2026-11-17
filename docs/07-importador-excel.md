# 07 — Importador de Excel y datos de ejemplo

Los archivos reales del periodo actual (`202720_Horario_Matutino.xlsx`, `..._Vespertino.xlsx`, `..._Sabatino.xlsx`) deben guardarse en `docs/samples/` **solo en un repositorio privado** (contienen nombres de docentes). Úsalos para desarrollar el importador y para los seeders de prueba.

## Estructura observada

Cada libro contiene:

- **`CRN Alumnos`**: materias por grupo, con CRN y alumnos (formato libre).
- **`Materias-Maestro`**: **tabla plana. Es la fuente a importar.** Columnas:

  | Libro | Columnas |
  |---|---|
  | Matutino | (A grupo) · `CRN` · `Subject` · `Course` · `MATERIAS` · `ID` · `DOCENTE` · `N° Alum` · `Hrs/clase` · `Estrategia` · `Carreras` |
  | Vespertino | (A grupo) · `CRN` · `SUBJECT` · `COURSE` · `MATERIAS` · `ID` · `DOCENTE` · `N° Alum` · `Hrs/Clase` · `ESTRATEGIA` |
  | Sabatino | `Carrera` (grupo) · `CRN` · `Subject` · `Course` · `MATERIAS` · `ID` · `DOCENTE` · `# Alumnos` · `Hrs/clase` · `ESTRATEGIA` · `Carrera` |

  La fila de encabezados no está siempre en la misma fila: **detéctala buscando la celda `DOCENTE`**. La columna A guarda la etiqueta del grupo (p. ej. `2o. ADME`, `5° EJVP`, `1°EJCO`, `8o. EDTP`), a veces con espacios o sin espacio tras el símbolo.
- **Una hoja por carrera** (p. ej. `ADME`, `CTCO`, `EJAD`): horario visual con celdas combinadas, aulas, días y horas. **No es fiable para importar automáticamente.** En la v1 solo se usa como referencia manual; no la parsees.

## Qué hacer con cada columna

- `Subject` + `Course` → `courses` (`subject_code`, `course_code`), única la pareja.
- `ID` → `teachers.employee_id`. El **nombre** es solo informativo. Si hay varios nombres para el mismo ID, reporta `warning` y conserva el más frecuente.
- `N° Alum` → `group_terms.student_count`.
- `Estrategia = Compactación` y `Carreras = ADME/DISGD` → indica que esa sección une los grupos de esas carreras (se reconstruye una sección con varios `GroupTerm`).
- `Hrs/clase` → `weekly_hours` informativo.
- Etiqueta de grupo → carrera (código) + `term_number` con una expresión regular tolerante (`^\s*(\d)\s*[°o\.]*\s*([A-Za-zÁÉÍÓÚÑ]+)`).
- Las carreras con prefijo `EJ` pertenecen a la modalidad **ejecutiva** **[POR CONFIRMAR]**.
- El código del periodo se toma del nombre del archivo (`202720`) y el texto "Ene – Abr 2027" de las hojas de horario.

## Valores especiales de docente

| Valor en Excel | Interpretación inicial | Acción |
|---|---|---|
| vacío / `0` | Sin asignar | `teacher_id = null`, advertencia si es presencial |
| `@` | Materia en línea | `delivery = online_async`, sin docente, **no es error** |
| `VV` | Vivo Virtual | `delivery = online_live`, sin docente |
| `CC` / "Clase Conecta" | Clase Conecta | `delivery = online_connect`, docente de sede si existe |
| ID con texto como `14 sem` | Dato basura | `info`, ignorar |

Estas interpretaciones están en `08-pendientes.md`; manténlas en una tabla de mapeo editable, no en `if` sueltos.

## Problemas conocidos en los datos (el reporte debe detectarlos)

- Mismo docente con grafía distinta (`Ortiz`/`Ortíz`; `Mariano de J.`/`Mariano De Jesus`).
- IDs numéricos sin ceros a la izquierda (`247112` frente a `'00295221'`): normalizar a **texto de 8 dígitos**.
- Años inconsistentes entre hojas (`Ene – Abr 2026` frente a `2027`).
- Materias con docente repetido que se empalman entre turnos.
- Faltan CRN (aparecen como `0`).

## Comportamiento

1. Subir uno o más libros → crear `import_batch`.
2. Leer solo `Materias-Maestro`; construir en memoria grupos, materias, docentes y secciones.
3. Generar `import_issues` (error / advertencia / info) y mostrar el reporte **antes** de escribir en la base.
4. Con confirmación, insertar en una transacción. Operación **idempotente**: reimportar el mismo archivo no duplica datos.
5. El periodo importado se crea como `closed` si es histórico o `draft` si es el actual.

## Seeders de desarrollo

Crear un seeder con una carrera de cada modalidad, 3 grupos, 15 materias, 10 docentes y 8 aulas con capacidades de 15 a 40, basado en los datos de ejemplo, **sin nombres reales** (usa nombres ficticios).
