# 09 — Dependencias

Laravel **13** requiere **PHP 8.3 o superior** (probado con 8.3 a 8.5). Antes de instalar algo, revisa `composer.json` y `package.json`: el starter kit de Vue + Inertia ya trae varias piezas y **no hay que duplicarlas**.

## Ya incluido en el starter kit (verifica, no reinstales)

Inertia (servidor y cliente), Vue 3, TypeScript, Tailwind CSS, Vite, autenticación, Pest y Pint. Revisa si también trae componentes de UI, íconos, `@vueuse/core`, ESLint y Prettier; si los trae, úsalos.

## PHP: producción

| Paquete | Para qué | Fase |
|---|---|---|
| `maatwebsite/excel` (^4) | Importar el Excel histórico y exportar horarios a Excel | 5 y 7 |
| `barryvdh/laravel-dompdf` (^3.1) | Exportar horarios a PDF imprimible (tablas simples) | 7 |
| `spatie/laravel-backup` | Respaldos de la base de datos del homelab | 7 |

## PHP: desarrollo

| Paquete | Para qué |
|---|---|
| `laravel/boost` | Guías y servidor MCP para agentes de IA. Su soporte para Antigravity **no está confirmado** (ver abajo). |
| `larastan/larastan` | Análisis estático. Nivel 6 o más; atrapa errores del código generado por el agente |

Comandos:

```bash
./vendor/bin/sail composer require maatwebsite/excel barryvdh/laravel-dompdf spatie/laravel-backup
./vendor/bin/sail composer require --dev laravel/boost larastan/larastan
```

Si Composer rechaza alguno por compatibilidad con Laravel 13, **no fuerces la instalación**: reporta el error y propone alternativa.

### Sobre Laravel Boost

`php artisan boost:install` genera archivos de guía para los agentes que soporta (Cursor, Claude Code, Codex, GitHub Copilot, Junie) y un servidor MCP (`php artisan boost:mcp`). Si ya existe un `AGENTS.md`, solo reemplaza el bloque `<laravel-boost-guidelines>` y **respeta el resto**, así que no pisa estas instrucciones. Antigravity no aparece en la documentación de Boost: instálalo y, si el agente permite configurar servidores MCP, apunta a `php artisan boost:mcp` (con Sail: `./vendor/bin/sail artisan boost:mcp`). Las reglas propias del proyecto van en `.ai/rules/`, que **sí** se versiona.

## JavaScript

| Paquete | Para qué | Cuándo |
|---|---|---|
| `date-fns` | Manejo de fechas, semanas y horas en la interfaz | Fase 2 |
| `vitest`, `@vue/test-utils`, `jsdom` | Pruebas de componentes críticos (cuadrícula, panel de alertas) | Fase 4 |

La **cuadrícula semanal** se construye a mano con CSS Grid y componentes Vue propios. **No uses** FullCalendar ni librerías de calendario: las vistas por recurso son de pago y el caso es más simple que un calendario general.

## Lo que NO se instala (a propósito)

- **Paquetes de roles/permisos** (Spatie Permission, etc.): hay dos roles y basta una columna `role` con un Enum.
- **Redis, Horizon, Telescope, Octane**: no hacen falta. La cola puede ser `database` para importar y exportar.
- **Librerías de formularios/validación en cliente** (vee-validate, etc.): Inertia `useForm` y los Form Requests son suficientes.
- **Librerías de drag and drop**: fuera de la v1.
- Para detectar consultas N+1 usa `Model::preventLazyLoading()` en desarrollo, que ya viene en Laravel.

## Opcional más adelante

- `spatie/laravel-activitylog`: bitácora de quién cambió cada asignación. Útil si más de una persona coordina.
- `@tanstack/vue-table`: solo si el starter kit ya lo usa para tablas.

## Regla

Cualquier dependencia que **no esté en este documento** requiere explicar por qué y esperar confirmación del usuario.
