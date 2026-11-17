---
trigger: always_on
---

Antes de ejecutar cualquier tarea, lee y sigue estrictamente todas las instrucciones, arquitectura, glosario y reglas de entorno Docker definidas en `AGENTS.md` (en la raíz de este repositorio) y la documentación en `docs/`.

Recuerda siempre que:
1. Este proyecto NO usa Laravel Sail. Todos los comandos de PHP, Artisan, Composer, Pest, Pint, Larastan y NPM se ejecutan mediante `docker exec escolar-app <comando>` (sin flags `-it`).
2. Trabaja estrictamente una fase a la vez según `docs/06-plan-de-implementacion.md` y ejecuta `docker exec escolar-app php artisan test` y `docker exec escolar-app ./vendor/bin/pint` antes de cerrar cada fase.
