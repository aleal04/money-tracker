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
- No crear, correr ni modificar migraciones; el esquema vive en `database/scripts` (scripts idempotentes). Orden de ejecución: `database` -> `tables` -> `indexes` -> `inserts` -> `logins` (todos los archivos de cada carpeta, por número). Cache `file` y queue `sync`: no hay tablas de cache/jobs.
- Database naming (as in `ssi`): tables in singular PascalCase without schema prefix (`Space`, `Period`, `Line`, `Card`, `Installment`, `Template`); primary key `Id<Table>`; foreign keys `Fk<Referenced>` (`FkPeriod`, `FkCarriedToLine`); other columns PascalCase; constraints `PK_<Table>_<PkColumn>`, `FK_<Table>_<Referenced>`, `UQ_<Table>_<Cols>`, `IX_<Table>_<Cols>`. Always write the exact table name and backtick it in SQL: MySQL is case-sensitive on Linux (`lower_case_table_names=0`) but not on macOS/Windows. Models set `$table`, `$primaryKey` and explicit relation keys; the JSON sent to the frontend stays camelCase (`toView()`), only the DB side is PascalCase.
- Audit columns on every table: `IsDeleted`, `CreatedBy`, `UpdatedBy`, `DeletedBy`, `CreatedAt`, `UpdatedAt`, `DeletedAt`. Eloquent fills `CreatedAt`/`UpdatedAt` (`CREATED_AT`/`UPDATED_AT` constants on each model); the `*By` columns stay NULL until there is a login.
- Logical delete only: models use `App\Models\Concerns\LogicalDeleteTrait` (global scope `IsDeleted = 0` on every query, `logicalDelete()` sets `IsDeleted = 1` and `DeletedAt`). Never call `delete()` in the app; use `withoutGlobalScopes()` only to look at deleted rows. Uniqueness among live rows uses VIRTUAL generated columns (`Space.ActiveName`, `Space.ActivePrimary`, `Card.ActiveName`, `Period.ActiveStart`, `Line.CuotasCardKey`) because MySQL has no partial indexes; `unique` validation rules must also filter `IsDeleted`.
- Format with `./vendor/bin/pint` and `pnpm lint`. Enable hooks with `git config core.hooksPath .githooks`.

## Money rules

- Money is integer cents in PHP; DB columns `decimal(14,2)`; never floats for arithmetic.
- Display `₡10.865,00` (dot thousands, comma decimals, `-₡30.900,00` for negatives).
- Amount input accepts `"5.900"`, `"5.900,50"`, `"5900,5"`, `"5900.5"`, `"₡ 10.865"`; garbage gives the field error "Monto inválido".
- Categories: `ingreso | servicio | gasto | ahorro | deuda_fija`; egreso = all but ingreso. `POR_TERMINAR_THRESHOLD = 3`. Monthly split (templates `mensual_repartido` and cuotas): equal parts across the periods of the space that start in the month, remainder cents to the first ones (biweekly: half 1 `ceil`, half 2 `floor`).
- Periods belong to a space (`FkSpace`, `StartDate`, `EndDate`; the primary space "Salario" is biweekly and is seeded by `inserts`). Endpoints take an optional `spaceId`/`space` and default to the primary space. Period label: "I Quincena Octubre 2026" / "II Quincena Octubre 2026".
- No authentication.
