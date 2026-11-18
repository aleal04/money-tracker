# Money Tracker

Personal finance app: Laravel 12 + Inertia 3 + Vue 3 (TypeScript), MySQL, pnpm, SCSS. Architecture follows the `ssi` project.

## Rules

- Reply to the user in Spanish. Code, comments and PHPDoc in English. All UI copy in Spanish.
- PHPDoc (English) on every method.
- Do not change dependencies without approval. Do not commit or push unless asked.
- Stick to the existing structure; do not create new base folders. Forbidden folders: `app/Actions`, `app/Data`, `app/Http/Resources`, `app/Rules`.
- Layering per domain: Route -> Controller (implements Contract) -> FormRequest -> readonly DTO -> Service (implements Contract) -> Repository (writes, transactions) / QueryRepository (reads) -> Model.
- Names: `XxxModel`, `XxxStoreDto` (readonly ctor props + `fromRequest()`/`toArray()`), `StoreXxxFormRequest`, `XxxControllerContract`, `XxxServiceContract`. Constants in `app/Constant/*Constant.php` (no Enums).
- Vue styles live in SCSS partials under `resources/scss` mirroring the Vue path in kebab-case, not inside the component.
- Use Context7 for library/framework documentation before relying on an API.
- Tests: PHPUnit on sqlite `:memory:` (run `php artisan config:clear` first), Vitest for the frontend. The test schema is built by `tests/Concerns/CreatesSchemaTrait.php` (mirror of the MySQL scripts; keep both in sync).
- No crear, correr ni modificar migraciones; el esquema vive en `database/scripts` (scripts idempotentes). Orden de ejecución: `database` -> `tables` -> `indexes` -> `logins` (todos los archivos de cada carpeta, por número). Cache `file` y queue `sync`: no hay tablas de cache/jobs.
- Format with `./vendor/bin/pint` and `pnpm lint`. Enable hooks with `git config core.hooksPath .githooks`.

## Money rules

- Money is integer cents in PHP; DB columns `decimal(14,2)`; never floats for arithmetic.
- Display `₡10.865,00` (dot thousands, comma decimals, `-₡30.900,00` for negatives).
- Amount input accepts `"5.900"`, `"5.900,50"`, `"5900,5"`, `"5900.5"`, `"₡ 10.865"`; garbage gives the field error "Monto inválido".
- Categories: `ingreso | servicio | gasto | ahorro | deuda_fija`; egreso = all but ingreso. `POR_TERMINAR_THRESHOLD = 3`. Monthly split: half 1 `ceil`, half 2 `floor`.
- Period label: "I Quincena Octubre" / "II Quincena Octubre".
- No authentication.
