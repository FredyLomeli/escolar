# 08 — Pendientes por confirmar

Regla: **no inventes la respuesta.** Impleméntalo como parámetro, catálogo o mapeo editable, con el valor por defecto indicado, y deja anotado en el resumen de la fase qué supusiste.

| # | Tema | Valor por defecto provisional | Quién confirma |
|---|---|---|---|
| 1 | Significado de `@`, `VV`, `CC` | `@` = en línea asíncrona, `VV` = Vivo Virtual (docente externo), `CC` = Clase Conecta (docente de sede) | Universidad |
| 2 | Horarios del bloque de **inglés** | Lunes a jueves 12:30–14:00 y 14:00–15:30 | Universidad |
| 3 | Materias de la **estadía** en ejecutivo | 3 materias + estadía | Plan de estudios |
| 4 | Catálogo de **aulas** y capacidades | Datos de ejemplo hasta recibir el catálogo real | Universidad |
| 5 | **Planes de estudio** completos de cada carrera | Capturables en la app; cargar una carrera de ejemplo | Universidad |
| 6 | Franjas horarias de **ejecutivo** (sábado, noche 19:00–22:00, entre semana 10:00–13:00) | Configurables en `shift_windows` | Universidad |
| 7 | Formato del **código de periodo** | **Confirmado:** `YY20` (Ene–May), `YY40` (May–Ago), `YY60` (Ago–Dic), editable como texto único | Universidad (confirmado) |
| 8 | Carreras `EJ*` son versiones ejecutivas de las escolarizadas | Se tratan como programas independientes de modalidad ejecutiva | Universidad |
| 9 | Orden de **SUBJ y CRSE** en los Excel (`Subject` = `LMFM`, `Course` = `HUS02`) | Se guardan tal cual vienen | Universidad |
| 10 | Rangos suaves de tamaño de sección | Objetivo 20; aviso bajo < 10 y alto > 30 | Coordinador |
| 11 | Disponibilidad de docentes: ¿existe registro actual? | Capturar en la app, sin dato = advertencia | Coordinador |
| 12 | Si se puede **publicar** con advertencias | Sí, solo errores bloquean | Coordinador |

## Fuera de la v1 (no implementar, no bloquear)

- Compactar ejecutivo con escolarizado (revalidación de materias con distinta clave).
- Alumnos que cargan un CRN de otro horario para repetir materia.
