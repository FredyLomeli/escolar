# Planeación Académica

Sistema web para la planeación cuatrimestral de grupos, docentes, aulas y horarios universitarios.

Stack: **Laravel 13** (PHP 8.3+), **Vue 3** (TypeScript + Inertia.js), **Tailwind CSS**, **MySQL / MariaDB**, **Pest**, **Pint**, **Larastan**.

---

## Entorno de desarrollo (Docker Homelab)

Este proyecto **no utiliza Laravel Sail**. Corre dentro del contenedor `escolar-app` conectado a la red `homelab-net` (`mysql-shared`). Todos los comandos deben ejecutarse dentro del contenedor:

### Levantar / reiniciar contenedores (desde la carpeta padre)

```bash
docker compose up -d --build
```

### Migraciones y datos semilla

```bash
docker exec escolar-app php artisan migrate
docker exec escolar-app php artisan migrate:fresh --seed
```

### Compilar frontend (Vite)

```bash
docker exec escolar-app npm install
docker exec escolar-app npm run build
```

### Pruebas, formato y análisis estático

```bash
# Pruebas automatizadas (limpia config cacheada y ejecuta Pest)
docker exec escolar-app composer test

# Formato de código (Laravel Pint)
docker exec escolar-app ./vendor/bin/pint --test

# Análisis estático (Larastan nivel 6)
docker exec escolar-app composer analyse
```

---

## Documentación del proyecto

- `AGENTS.md`: instrucciones permanentes del agente (stack, convenciones, glosario, entorno Docker y reglas de trabajo).
- `docs/01` a `docs/09`: visión, dominio, modelo de datos, validaciones, pantallas, plan por fases, importador Excel, pendientes por confirmar y dependencias permitidas.

Antes de la Fase 1 conviene tener a la mano el catálogo de aulas y los planes de estudio; si aún no llegan, se usarán datos de ejemplo configurables (ver `docs/08-pendientes.md`).
