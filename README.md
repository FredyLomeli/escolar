# Planeación Académica — paquete de documentación

Documentación para que un agente de desarrollo (Antigravity) construya la aplicación por fases.

## Contenido

- `AGENTS.md`: instrucciones permanentes del agente (stack, convenciones, reglas de trabajo).
- `docs/01` a `docs/09`: visión, dominio, modelo de datos, validaciones, pantallas, plan por fases, importador, pendientes y dependencias.

Stack: Laravel 13 (PHP 8.3 o superior), Vue 3, Inertia, Tailwind, MySQL, Pest.

## Cómo empezar

1. Crea un repositorio **privado** y copia estos archivos en la raíz (`AGENTS.md`, `README.md`, carpeta `docs/`).
2. Guarda los tres Excel actuales en `docs/samples/`.
3. Si tu versión de Antigravity usa otro archivo de reglas (por ejemplo `GEMINI.md` o una carpeta de reglas del workspace), copia ahí el contenido de `AGENTS.md` o haz que lo referencie.
4. Pide al agente la **Fase 0** y avanza fase por fase revisando cada resumen.

Antes de la Fase 1 conviene tener a la mano el catálogo de aulas y los planes de estudio; si aún no llegan, el agente usará datos de ejemplo (ver `docs/08-pendientes.md`).
