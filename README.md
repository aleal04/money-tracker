# Money Tracker

App personal de finanzas (Laravel 12 + Inertia + Vue). Registra ingresos, gastos y presupuestos; instalable como PWA.

> **ADVERTENCIA: no hay login.** No expongas la app públicamente hasta que se agregue autenticación.

## Requisitos

- PHP 8.2+ y Composer
- Node + pnpm
- Docker con un contenedor `mysql` (MySQL 8.4)

## Base de datos

El esquema vive en scripts idempotentes MySQL en `database/scripts` (no hay migraciones). Antes de ejecutarlos, edita `database/scripts/logins/1. Application - Login.sql` y cambia `<CAMBIAR_PASSWORD_ALFANUMERICA>` por una contraseña con solo letras y números (los especiales dan problemas en URLs y `.env`). No guardes la real en git.

Ejecútalos en este orden contra el contenedor:

```bash
cd database/scripts
docker exec -i mysql mysql -uroot -p < "database/1. Money Tracker - Database.sql"
docker exec -i mysql mysql -uroot -p < "tables/1. Money Tracker - Tables.sql"
docker exec -i mysql mysql -uroot -p < "indexes/1. Money Tracker - Indexes.sql"
docker exec -i mysql mysql -uroot -p < "inserts/1. Money Tracker - Inserts.sql"   # espacio principal "Salario"
docker exec -i mysql mysql -uroot -p < "logins/1. Application - Login.sql"
```

## Instalación

```bash
composer install && pnpm install
cp .env.example .env   # SESSION_DRIVER=file (no hay migración de sessions); completa DB_PASSWORD; APP_TIMEZONE=America/Costa_Rica
php artisan key:generate
git config core.hooksPath .githooks   # activa los hooks
```

## Desarrollo

```bash
composer run dev
# o: php artisan serve  +  pnpm dev
```

## Tests y calidad

```bash
php artisan config:clear && php artisan test
pnpm vitest run
./vendor/bin/pint --test
pnpm lint
pnpm typecheck
```

## Producción

```bash
pnpm build
```

## Nota

Los restos sin commitear de NestJS en `/api` y `/web` se ignoran.
