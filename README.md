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

## Espacios

Un espacio es una "bolsa" de dinero (Salario, Ahorro, Servicios digitales...) con sus propios periodos. Hay uno principal ("Salario", quincenal) y se administran en el menú Espacios.

- **Frecuencia**: semanal, quincenal, mensual o personalizada (N días). Propone las fechas del siguiente periodo; se pueden ajustar sin traslapes.
- **Saldo**: Saldo anterior + Entró − Gastado = Restante. El restante (positivo o negativo) pasa como saldo anterior al siguiente periodo; el primero parte del saldo inicial del espacio.
- **Transferencias**: en una línea de gasto, "Enviar a espacio" crea, al pagarla, un ingreso "Desde <espacio>" en el destino, en el periodo que contiene la fecha de inicio del periodo de origen (se crea si falta). Despagar, cambiar el monto o borrar la línea origen se propaga; la línea destino no se edita.
- **Cerrar periodo**: una transferencia sin pagar no entra al arrastre general; pasa al siguiente periodo como su propia línea "↩ nombre". Reabrir la deshace mientras no esté pagada.
- **Borrar**: un espacio solo se borra si está vacío (sin periodos, tarjetas, plantillas ni transferencias que lo apunten); el principal no se puede borrar, pero se puede cambiar cuál es.

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
