# AGENTS.md — Sistema de Planeación Académica

Instrucciones permanentes para el agente de desarrollo. Léelas completas antes de cada tarea.

## 1. Qué estamos construyendo

Una aplicación web para que el **coordinador académico** de una universidad planee cada cuatrimestre (3 por año): asignar docentes y aulas a las materias de cada grupo, validar choques y capacidades, y proponer una planeación casi lista que el coordinador solo revisa y ajusta. El usuario principal **no conoce el proceso a fondo**, así que la herramienta debe guiar, advertir y proponer.

Documentación de dominio (léela en este orden):

1. `docs/01-vision-y-alcance.md`
2. `docs/02-dominio-y-reglas.md`  ← reglas de negocio, la fuente de verdad
3. `docs/03-modelo-de-datos.md`
4. `docs/04-validaciones-y-propuestas.md`
5. `docs/05-pantallas.md`
6. `docs/06-plan-de-implementacion.md`  ← qué hacer y en qué orden
7. `docs/07-importador-excel.md`
8. `docs/08-pendientes.md`  ← lo no confirmado; NO lo inventes
9. `docs/09-dependencias.md`  ← qué paquetes instalar y cuáles no

## 2. Stack

- **Laravel 13**, que requiere **PHP 8.3 o superior**. Verifica con `composer show laravel/framework` y `php -v`. Los *attributes* de PHP son opcionales en Laravel 13: respeta el estilo que ya traiga el proyecto y sé consistente.
- **Vue 3** (`<script setup>`, Composition API, TypeScript) con **Inertia.js**. El proyecto **ya fue creado** con el starter kit oficial de Laravel para Vue + Inertia (incluye autenticación): no lo reemplaces. Revisa `composer.json` y `package.json` antes de agregar algo que ya venga incluido.
- Dependencias adicionales permitidas: ver `docs/09-dependencias.md`.
- **Tailwind CSS**. Componentes propios simples; evita librerías de UI pesadas salvo que se justifique.
- **MySQL 8** (las consultas deben funcionar también en PostgreSQL; no uses funciones exclusivas de un motor sin necesidad).
- **Pest** para pruebas, **Laravel Pint** para formato.
- Zona horaria de la app: `America/Mexico_City`. Idioma de la interfaz: **español (México)**.

## 3. Convenciones

- Identificadores de código (tablas, modelos, clases, variables) en **inglés**. Textos de interfaz, mensajes de validación y reportes en **español**. Usa el glosario de la sección 6.
- Controladores delgados. La lógica de negocio va en `app/Domain/...` (acciones, servicios, reglas), en clases PHP puras y **probables sin HTTP**.
- Entradas siempre por *Form Requests*; autorización con *Policies*; valores fijos con *Enums*.
- Inertia: pages en `resources/js/Pages`, componentes reutilizables en `resources/js/Components`, lógica reutilizable en `resources/js/Composables`. **Ninguna regla de negocio vive en Vue**; el frontend solo muestra lo que calcula el backend.
- Migraciones solo hacia adelante, con llaves foráneas e índices explícitos. Seeders con datos de ejemplo realistas (ver `docs/07-importador-excel.md`).
- Roles iniciales: `admin` y `coordinator`. No agregues más roles en la v1.

## 4. Reglas de trabajo (importante)

1. **No inventes reglas de negocio.** Si algo no está en `docs/`, o está en `docs/08-pendientes.md`, impleméntalo como **parámetro configurable** (tabla `settings` o catálogo) con un valor por defecto razonable, déjalo anotado y sigue.
2. **Nada de horarios, días, horas, número de materias, tamaños de grupo ni bloques de inglés fijos en el código.** Todo eso es configuración en base de datos.
3. Trabaja **por fases** según `docs/06-plan-de-implementacion.md`. No adelantes fases. Al terminar cada una: pruebas en verde, `pint` limpio, y un resumen breve de qué se hizo, qué quedó pendiente y qué decisiones tomaste.
4. **Plan detallado y visto bueno obligatorio:** Antes de avanzar con cualquier tema o programar una fase, genera y presenta un plan de implementación a detalle (archivos a crear/modificar, dependencias, configuraciones y pruebas). **NO comiences la ejecución ni modifiques código hasta que el usuario dé su visto bueno explícito.**
5. Cada regla de validación de `docs/04-validaciones-y-propuestas.md` lleva **pruebas unitarias** (caso correcto y caso con error).
6. No guardes secretos en el repo. `.env.example` sí, `.env` no. El repositorio contiene datos de personas (docentes): manténlo **privado**.
7. Si encuentras una contradicción entre documentos, **detente y pregunta** en lugar de elegir en silencio.
8. No agregues dependencias que no estén en `docs/09-dependencias.md` sin explicar por qué y esperar confirmación. Si Composer o npm rechazan un paquete por compatibilidad con Laravel 13, no fuerces la instalación: reporta y propón alternativa.
9. **QA visual obligatorio en cambios de interfaz:** Siempre que un subpaso o fase incluya creación o modificación de interfaz gráfica (Vue/Inertia), compila los assets (`npm run build`) y muéstrala al usuario (rutas exactas, credenciales de prueba y verificación visual) para que pase por QA antes de continuar.

## 5. Definición de terminado (por fase)

- Migraciones, modelos, factories y seeders funcionando desde cero (`php artisan migrate:fresh --seed`).
- Pruebas automáticas de lo nuevo y de lo anterior en verde (`php artisan test`).
- Interfaz usable en escritorio, en español, con estados de carga, vacío y error.
- Sin código muerto ni TODO sin explicar.

## 6. Glosario (español → código)

| Español | Código |
|---|---|
| Periodo / ciclo escolar / cuatrimestre calendario | `AcademicPeriod` |
| Carrera / programa | `Program` |
| Plan de estudios | `Curriculum` |
| Materia (clave SUBJ + CRSE) | `Course` |
| Cuatrimestre del plan (1 a 9) | `term_number` |
| Grupo (generación de una carrera) | `StudentGroup` |
| Grupo en un periodo concreto | `GroupTerm` |
| Sección (la clase que se imparte; tiene CRN) | `Section` |
| Sesión semanal de una sección | `SectionMeeting` |
| Compactación | sección con varios `GroupTerm` |
| Docente (llave: ID docente) | `Teacher` |
| Aula | `Classroom` |
| Modalidad (escolarizado / ejecutivo) | `Modality` |
| Turno / franja | `Shift` / `ShiftWindow` |
| Bloque reservado (p. ej. inglés) | `ReservedBlock` |

### Entorno de ejecución real (Homelab Docker — OBLIGATORIO)

- Este proyecto **NO usa Laravel Sail**; corre en un contenedor Docker ya levantado llamado **`escolar-app`** (conectado a la base de datos **`mysql-shared`** en la red `homelab-net`).
- El código fuente vive en `~/homelab/apps/escolar/src` en el host y está montado en `/var/www/html` dentro del contenedor.
- **Cualquier comando de PHP, Artisan, Composer, Pest, Pint, Larastan o NPM DEBE ejecutarse dentro del contenedor**, nunca directo en el host:
  - Artisan / Migraciones: `docker exec escolar-app php artisan ...`
  - Pruebas (Pest): `docker exec escolar-app composer test` (limpia `config:clear` antes de `php artisan test`)
  - Formato (Pint): `docker exec escolar-app ./vendor/bin/pint`
  - Análisis estático (Larastan): `docker exec escolar-app composer analyse` (ejecuta `phpstan analyse --memory-limit=512M`)
  - Composer: `docker exec escolar-app composer ...`
  - Node / Vite: `docker exec escolar-app npm run build` (o `npm install ...`)
- Si creas archivos o migraciones dentro del contenedor como `root`, asegúrate de que los permisos de `storage` y `bootstrap/cache` permitan escritura a `www-data`.
