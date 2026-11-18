# Money Tracker — Laravel + Inertia + Vue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the money tracker as a single Laravel 12 + Inertia 3 + Vue 3 (TS) SPA on MySQL, following the `ssi` project's architecture, with the same business rules and visual design as the NestJS version.

**Architecture:** ssi layering per domain: Route → Controller (implements Contract) → FormRequest → readonly DTO → Service (implements Contract) → Repository (writes, transactions) / QueryRepository (reads) → Model. Pure money/period/installment math lives in small final calculator/helper classes with unit tests. Inertia pages + composables on the frontend, SCSS styling.

**Tech Stack:** Laravel 12, PHP ^8.2, inertia-laravel 3, @inertiajs/vue3 3, Vue 3.5 + TS, Vite 7, pnpm, SCSS, @fortawesome/vue-fontawesome, PHPUnit 11 (sqlite :memory:), Vitest + @vue/test-utils + happy-dom, Pint, Biome, MySQL 8.4.

**Spec:** `docs/superpowers/specs/2026-11-18-money-tracker-laravel-design.md` (business rules: `docs/superpowers/specs/2026-11-17-money-tracker-design.md` §5–§7 + Laravel spec §6).

**Reference implementations (read, port, don't copy blindly):**

- Business logic + tests (NestJS, branch `feat/v1`): `git show feat/v1:api/src/<path>` and `git show feat/v1:api/test/<path>`. Key files: `api/src/shared/domain/money.ts`, `period-key.ts`, `api/src/installments/domain/installment.ts`, `api/src/periods/domain/summary.ts`, `api/src/templates/domain/template.ts`, `api/src/periods/application/{create-period,sync-cuotas-lines,close-period,reopen-period,line-commands,period-queries}.use-case.ts`, `api/src/installments/application/*.use-case.ts`, and the tests under `api/test/{unit,integration,e2e}`.
- Frontend design (branch `feat/v1`): `web/src/{main.css,theme.ts,format.ts,components/*.vue,views/*.vue}` — port look & behavior to Inertia pages + SCSS.
- Architecture conventions: `/Users/andreyleal/Documents/Apps/ssi` (read-only): `CLAUDE.md`, `.ai/rules/`, `app/Helpers/ReturnResponse.php`, `app/Repositories/Concerns/RunsTransactionStepsTrait.php`, `app/Repositories/BaseQueryRepository.php`, any domain e.g. Reprimand (controller/contract/request/DTO/service/repositories/model/routes/page/composable/tests), `resources/js/{app.js,Layouts,setup}`, `pint.json`, `biome.json`, `.githooks/`.

## Global Constraints

- Follow ssi conventions exactly: folders `app/{Constant,Contracts/{Controllers,Services}/<D>,DTOs/<D>,Http/{Controllers,Requests}/<D>,Models/<D>,Repositories/{Concerns,<D>},Services/<D>,Helpers}`; names `XxxModel`, `XxxStoreDto` (readonly ctor props + `fromRequest()`/`toArray()`), `StoreXxxFormRequest`, `XxxControllerContract`, `XxxServiceContract`; constants in `app/Constant/*Constant.php` (no Enums). **Never create** `app/Actions`, `app/Data`, `app/Http/Resources`, `app/Rules`.
- PHPDoc on every method (English). Code/comments in English; **all UI copy in Spanish**.
- Money: integer céntimos in PHP; `decimal(14,2)` columns; never floats for arithmetic. Display `₡10.865,00` (dot thousands, comma decimals, `-₡30.900,00` for negatives).
- Amount input accepts `"5.900"`, `"5.900,50"`, `"5900,5"`, `"5900.5"`, `"₡ 10.865"`; garbage → validation error "Monto inválido" on that field.
- Categories `ingreso | servicio | gasto | ahorro | deuda_fija`; egreso = all but ingreso. `POR_TERMINAR_THRESHOLD = 3`. Monthly split: half 1 `ceil`, half 2 `floor`.
- Period label `"I Quincena Octubre"` / `"II Quincena Octubre"`.
- No authentication (spec decision). No auth/permission middleware.
- Tests: PHPUnit on sqlite `:memory:`; every migration must run on sqlite and MySQL. Run `php artisan config:clear` before tests.
- Do not touch leftover untracked `api/` and `web/` folders (NestJS build leftovers, contain the user's old `.env.local`).
- Dependencies: only those listed in Tech Stack plus what the Laravel/Inertia/Vite scaffold requires; anything else needs a ledger ruling.
- Commits end with a blank line then `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`. Never commit `.env`.

## Review Focus

1. Arrastre money correctness end to end (close → one arrastre line in next period; reopen guards; no double counting) — Task 5 tests.
2. Odd céntimos (monthly split, Tasa 0 faltante with unrounded cuota) — Task 2 tests.
3. Cuotas line sync (actual stays user-owned; only open, unpaid, not-older-than-latest-I periods) — Task 6 tests.
4. Double submit of "Nueva quincena" / "Cerrar" → no duplicates — Tasks 4–5 tests.
5. Phone-typed amounts parsed server-side — Task 2 unit + Task 4 feature tests.

---

### Task 1: Laravel + Inertia + Vue scaffold, tooling, ssi base helpers

**Files:** Laravel 12 app at repo root (keep `IDEAS.md`, `docs/`); `app/Helpers/ReturnResponse.php` (composer `files` autoload), `app/Repositories/Concerns/RunsTransactionStepsTrait.php`, `app/Repositories/BaseQueryRepository.php` (adapted from ssi, no SQL Server specifics), `app/Http/Middleware/HandleInertiaRequests.php` (shared props: `appVersion` only), `resources/js/app.ts`, `resources/js/Layouts/AppLayout.vue` (placeholder), `resources/js/Pages/Dashboard/Index.vue` (placeholder "Inicio"), `resources/scss/app.scss`, `pint.json`, `biome.json`, `.githooks/pre-push`, `CLAUDE.md`, `.env.example` (MySQL vars, `APP_TIMEZONE=America/Costa_Rica` — set `config/app.php` timezone from it), `phpunit.xml` (sqlite :memory:), `vitest.config.ts`, `tests/Feature/SmokeTest.php`.

- [ ] **Step 1:** Create Laravel 12 in a temp dir (`composer create-project laravel/laravel:^12.0`), move into repo root without overwriting `IDEAS.md`/`docs/`. Install `inertiajs/inertia-laravel` 3 server-side; frontend via pnpm: `vue`, `@inertiajs/vue3` 3 (+ `@inertiajs/vite` if ssi uses it), `@vitejs/plugin-vue`, `typescript`, `vue-tsc`, `sass`, `@fortawesome/{fontawesome-svg-core,free-solid-svg-icons,free-regular-svg-icons,vue-fontawesome}`, `vitest`, `@vue/test-utils`, `happy-dom`, `@biomejs/biome`, `axios`. Remove Tailwind if the skeleton adds it. Verify syntax with Context7 for Inertia 3 setup.
- [ ] **Step 2:** Copy/adapt ssi base helpers listed above; `CLAUDE.md` with the ssi rules that apply (Spanish replies, English code/PHPDoc, PHPDoc on every method, no dependency changes without approval, no commit/push unless asked, stick to structure, forbidden folders, use Context7) plus this project's money rules.
- [ ] **Step 3:** `routes/web.php`: `GET /` → `DashboardController@index` rendering `Dashboard/Index`; `Route::fallback(fn () => abort(404))`.
- [ ] **Step 4: Test** `tests/Feature/SmokeTest.php`: `GET /` → 200 and `assertInertia(fn ($p) => $p->component('Dashboard/Index'))`.
- [ ] **Step 5:** `php artisan config:clear && php artisan test` → pass; `pnpm build` → ok; `pnpm vitest run` → ok (one trivial test); `./vendor/bin/pint --test` → ok.
- [ ] **Step 6:** Commit `chore: Laravel + Inertia + Vue scaffold with ssi conventions`.

### Task 2: Pure calculators (money, period keys, installments, summary, templates)

**Files:** `app/Helpers/MoneyHelper.php` (final, static), `app/Helpers/PeriodKeyHelper.php`, `app/Services/Installment/InstallmentCalculator.php`, `app/Services/Period/PeriodSummaryCalculator.php`, `app/Services/Template/TemplateExpander.php`, `app/Constant/{CategoryConstant,LineSourceConstant,TemplateFrequencyConstant,InstallmentTypeConstant,InstallmentStatusConstant}.php`; tests `tests/Unit/{MoneyHelperTest,PeriodKeyHelperTest,InstallmentCalculatorTest,PeriodSummaryCalculatorTest,TemplateExpanderTest}.php`.

**Interfaces (port of feat/v1 domain files, same semantics):**

- `MoneyHelper::toCents(string|int|float|null): ?int` (accepts negatives), `fromCents(int): string` ("10865.00"), `parseAmount(string): ?int` (non-negative, es-CR rules), `splitMonthly(int $cents, int $half): int`, `format(int $cents): string` ("₡10.865,00").
- `PeriodKeyHelper::label(int $y, int $m, int $h): string`, `next(int $y, int $m, int $h): array{year:int,month:int,half:int}`, `monthKey(int $y, int $m): string`, `prevMonthKey(int $y, int $m): string`.
- `InstallmentCalculator::cuota(array|object $i): int`, `saldoFaltanteCalculado(...)`, `saldoFaltante(...): array{value:int,calculated:int,diff:?int}`, `status(...): string`, `advance(int $paid, int $total, string $last, string $target): array{paid:int,lastAdvanced:string}`, `cuotasLineAmount(iterable $installments, int $half): int`. Input shape: `type, totalInstallments, paidInstallments, initialBalance, installmentAmount, balanceOverride` (céntimos).
- `PeriodSummaryCalculator::lineValue(...)`, `summarize(iterable $lines): array{ingreso,egreso,disponible,faltaPorPagar,byCategory}` (carried lines excluded from everything, ingreso never in faltaPorPagar).
- `TemplateExpander::expand(iterable $templates, int $half): array<array{templateId,name,category,estimated}>`.

- [ ] **Step 1:** Port every assertion from `feat/v1:api/test/unit/{money,period-key,installment,finance}.test.ts` to PHPUnit (same values: Reloj 26502262/24 → cuota 1104261, faltante 13251131; Camilo 59266620; ExtremeTech override diff -6212800; status thresholds; advance cases incl. year wrap; Excel quincena egreso 49309600 / disponible 6453600 / faltaPorPagar 3090000; split 2335 → 1168/1167; parseAmount cases; format '₡10.865,00', '-₡30.900,00').
- [ ] **Step 2:** Run → FAIL. **Step 3:** implement. **Step 4:** `php artisan test --testsuite=Unit` → PASS. **Step 5:** Commit `feat: money, period and installment calculators`.

### Task 3: Migrations and models

**Files:** `database/migrations/*_create_{cards,templates,periods,lines,installments}_table.php`, `app/Models/{Period/PeriodModel,Period/LineModel,Installment/CardModel,Installment/InstallmentModel,Template/TemplateModel}.php`, `tests/Feature/Database/SchemaTest.php`.

Schema = original spec §5 in snake_case, `decimal(14,2)` money, `periods` UNIQUE(year,month,half), `lines.carried_to_line_id` self-FK nullOnDelete, `lines.period_id` cascade, `installments.card_id` restrict, `cards.name` unique, `installments.last_advanced_month` string(7) not null. Cuotas uniqueness: generated column `cuotas_card_id` (= card_id when source='cuotas') + UNIQUE(period_id, cuotas_card_id) — must work on MySQL 8.4 and sqlite; if sqlite can't express it, enforce in repository too and note it.

- [ ] **Step 1: Tests:** duplicate period → exception; two cuotas lines same (period, card) → exception; money round-trip exact (1086500 céntimos → "10865.00" → 1086500); deleting a period cascades lines.
- [ ] **Step 2–4:** FAIL → implement (models: `$table`, `$fillable` or ssi style, casts) → PASS. Also run `php artisan migrate` against the local MySQL **only if** `.env` exists with DB credentials; otherwise report skipped.
- [ ] **Step 5:** Commit `feat: database schema and models`.

### Task 4: Period domain — create period, lines CRUD, queries

**Files:** `routes/periods.php` (required from web.php), `app/Http/Controllers/Period/{PeriodController,LineController}.php`, `app/Contracts/Controllers/Period/*`, `app/Contracts/Services/Period/*`, `app/Http/Requests/Period/{StorePeriodFormRequest,StoreLineFormRequest,UpdateLineFormRequest}.php`, `app/DTOs/Period/*`, `app/Services/Period/{PeriodService,LineService,CuotasSyncService}.php`, `app/Repositories/Period/{PeriodRepository,PeriodQueryRepository,LineRepository,LineQueryRepository}.php`, `tests/Feature/Period/*Test.php`.

Endpoints (Inertia page + JSON, kebab-case): `GET /periods` → latest period redirect or `Period/Index` (empty → create form); `GET /periods/{id}` → `Period/Show` page; `GET /periods/{id}/data` → JSON `{period:{id,year,month,half,closed,label}, prevId, nextId, lines:[...+carried], summary}`; `POST /periods/store` {year,month,half}; `POST /periods/next`; `POST /periods/{id}/lines/store` {category,name,estimated,actual?,paid?}; `PUT /lines/{id}/update`; `POST /lines/{id}/toggle-paid`; `DELETE /lines/{id}/delete`; `GET /lines/{id}/arrastre-detail`.

Rules (port `create-period`, `sync-cuotas-lines`, `line-commands`, `period-queries` use cases): create is idempotent (existing key → same period, no new lines); half 1 advances installments; template lines `source=plantilla`; `CuotasSyncService::sync(?int $periodId)` with the final-review rules (insert `actual=null`, update estimated only, skip paid/closed, all-open scope limited to periods ≥ latest half-1 month). Lines: carried lines immutable (409 "Línea arrastrada"); arrastre line cannot be deleted; un-paying a paid line in a closed period → 409; paying allowed. Amounts parsed with `MoneyHelper::parseAmount` in FormRequests (custom closure rule; message "Monto inválido").

- [ ] **Step 1: Feature tests** porting `feat/v1:api/test/integration/create-period.test.ts` and the period/line parts of `api/test/e2e/periods.e2e.test.ts` (Excel quincena via HTTP → summary 49309600/6453600/3090000; "5.900"/"₡ 10.865"/"5.900,50" stored correctly; 'abc' → 422 with errors.estimated "Monto inválido"; double POST next → no duplicate keys; template expansion values; Reloj advance; cuotas line 552131 then 677861; paid cuotas line untouched).
- [ ] **Step 2–4:** FAIL → implement → PASS (full suite).
- [ ] **Step 5:** Commit `feat: periods and lines`.

### Task 5: Close / reopen (arrastre)

**Files:** extend `PeriodService`/`PeriodRepository` (or `PeriodCloseService`), `PeriodController` actions `GET /periods/{id}/close-preview`, `POST /periods/{id}/close`, `POST /periods/{id}/reopen`; `tests/Feature/Period/ClosePeriodTest.php`.

Port `close-period` + `reopen-period` use cases and all their tests (one arrastre line 'Arrastre I Quincena Octubre' 3090000 in Deudas fijas; nothing unpaid → no line; close twice → one line; next period pre-existing; chain over 3 periods; next period closed → 409 'La quincena siguiente está cerrada; reabrila primero'; reopen with unpaid arrastre → undone; paid → 409 'No se puede reabrir: el arrastre ya está pagado'; carried onward → 409 'Reabrí primero la quincena siguiente'; deleting arrastre → 409). Use `runTransactionSteps` + `lockForUpdate()` on the period row.

- [ ] Steps: tests → FAIL → implement → PASS → commit `feat: close and reopen quincena with arrastre`.

### Task 6: Installment domain (cards + cuotas)

**Files:** `routes/installments.php`, controllers/contracts/requests/DTOs/services/repositories under `Installment`, `tests/Feature/Installment/*Test.php`.

Endpoints: `GET /installments` → `Installment/Index` page; `GET /installments/get-card-list` → JSON CardView[] (installments with cuota, saldo{value,calculated,diff}, status; totals{cuotaMensual, saldoFaltante, porQuincena:[h1,h2]} excluding terminada); `POST /cards/store` {name} (duplicate → 422 'Ya existe una tarjeta con ese nombre'); `POST /installments/store`; `PUT /installments/{id}/update`; `DELETE /installments/{id}/delete`; `POST /installments/{id}/toggle-cancel-soon`. New installments `last_advanced_month = prevMonthKey(now in America/Costa_Rica)`. Every installment mutation calls `CuotasSyncService::sync()` in the same transaction. Validation: total ≥ 1, 0 ≤ paid ≤ total, installmentAmount required for minicuotas, amounts parsed.

- [ ] Steps: port `feat/v1:api/test/e2e/installments.e2e.test.ts` + final-review sync tests (II Nov unaffected after I Dic exists) → FAIL → implement → PASS → commit `feat: cards and installments`.

### Task 7: Template domain

**Files:** `routes/templates.php`, Template controller/contract/requests/DTOs/service/repositories, `tests/Feature/Template/*Test.php`.

Endpoints: `GET /templates` → `Template/Index`; `GET /templates/get-template-list`; `POST /templates/store`; `PUT /templates/{id}/update`; `DELETE /templates/{id}/delete`. half required only for mensual_en_quincena.

- [ ] Steps: port `templates.e2e.test.ts` → FAIL → implement → PASS → commit `feat: templates`.

### Task 8: App shell — layout, sidebar, topbar, dark mode, styles

**Files:** `resources/js/Layouts/AppLayout.vue` (default layout in `app.ts` resolve), `resources/js/Components/UI/{AppSidebar,AppTopbar,Icon,Logo}.vue`, `resources/js/Composables/shared/useTheme.ts`, `resources/js/Constants/...`, `resources/scss/{ui/_tokens.scss,ui/_base.scss,components/*.scss}`, `resources/views/app.blade.php` (no-flash theme script, Inter font, manifest), `tests/js/Composables/useTheme.test.ts`.

Port from `feat/v1:web/src/{main.css,theme.ts,components/NavBar.vue,components/Icon.vue,components/Logo.vue}`: tokens (cream/teal/yellow + dark values), sidebar always visible (64px icon rail < md, 216px ≥ md, menu at top: Inicio, Quincenas, Cuotas, Plantillas — links to `/`, `/periods`, `/installments`, `/templates`), topbar fixed (theme toggle with moon / half-circle icons, "Mi cuenta" pill without logout since there is no login), full-width content. SCSS mirrors component paths in kebab-case; no `<style>` in .vue.

- [ ] Steps: useTheme test (system default, toggle persists, no localStorage crash) → FAIL → implement → `pnpm vitest run`, `pnpm build`, `pnpm types` → commit `feat: app shell, theme and styles`.

### Task 9: Pages — Quincena (Period/Index + Period/Show)

**Files:** `resources/js/Pages/Period/{Index,Show}.vue`, `resources/js/Components/Pages/Period/{SummaryCards,CategoryBlock,LineForm,ArrastreDetail,ClosePeriodDialog,CreatePeriodForm}.vue`, `resources/js/Composables/Period/{usePeriodShow,useLineForm}.ts`, `resources/js/Types/Pages/Period/*.ts`, `resources/js/Composables/shared/format.ts` (`formatCRC`, `diffLabel`, `plainAmount`), `tests/js/...`.

Port behavior from `feat/v1:web/src/views/QuincenaView.vue` + components: header with prev/next, Cerrar/Reabrir, Nueva quincena; summary cards; **category tabs** with counts + pending dot, `?tab=` in URL, default first tab with pending; table rows (paid toggle rules: disabled when carried, busy, or closed && paid), amount = actual ?? estimated, diff label, edit via pencil (prefilled form, PATCH-like PUT, delete with inline confirm, hidden for arrastre/carried), LineForm Pagado checkbox on create, arrastre expandable detail, close dialog with preview. Field errors from 422 shown under fields; 409 messages inline.

- [ ] Steps: format tests (exact strings) + composable test → implement → build/types/tests → commit `feat: quincena pages`.

### Task 10: Pages — Inicio, Cuotas, Plantillas

**Files:** `resources/js/Pages/{Dashboard/Index,Installment/Index,Template/Index}.vue`, components/composables/types per domain; backend `DashboardController` returning JSON data endpoint `GET /dashboard/data` (latest period detail, cards, template count) via existing services.

Port from `feat/v1:web/src/views/{HomeView,CuotasView,PlantillasView}.vue`: Inicio (greeting, current period, summary cards, pendientes con pagar en un clic, tarjetas panel, empty-state 3 pasos with create form); Cuotas (groups Activas/Por terminar/Terminadas, cancel-soon tag, forms, totals); Plantillas (list, forms, frequency labels, active toggle, note).

- [ ] Steps: Feature test for `/dashboard/data`; implement pages → build/types/tests → commit `feat: inicio, cuotas and plantillas pages`.

### Task 11: README, production build, final checks

**Files:** `README.md` (Spanish: requirements PHP 8.2+, Composer, Node + pnpm, Docker MySQL 8.4 with DB/user; `.env` setup; `php artisan migrate`; `composer run dev` / `php artisan serve` + `pnpm dev`; tests; production build; note: **no login — do not expose publicly**), `.githooks` enabled instructions.

- [ ] Steps: full `php artisan test`, `pnpm vitest run`, `pnpm build`, `pnpm types`, `pint --test`, `biome check` → all pass → commit `docs: README`.
