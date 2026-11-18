# Money Tracker

App personal de finanzas (Laravel 12 + Inertia + Vue). Registra ingresos, gastos y presupuestos; instalable como PWA.

> **ADVERTENCIA: no hay login.** No expongas la app públicamente hasta que se agregue autenticación.

## Requisitos

- PHP 8.2+ y Composer
- Node + pnpm
- Docker con un contenedor `mysql` (MySQL 8.4)

## Base de datos

Dentro del contenedor (`docker exec -it mysql mysql -uroot -p`). Usa una contraseña alfanumérica: los caracteres especiales dan problemas en URLs y `.env`.

```sql
CREATE DATABASE money_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'money'@'%' IDENTIFIED BY '<TU_PASSWORD_ALFANUMERICA>';
GRANT ALL PRIVILEGES ON money_tracker.* TO 'money'@'%';
FLUSH PRIVILEGES;
```

## Instalación

```bash
composer install && pnpm install
cp .env.example .env   # SESSION_DRIVER=file (no hay migración de sessions); completa DB_PASSWORD; APP_TIMEZONE=America/Costa_Rica
php artisan key:generate
php artisan migrate
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
