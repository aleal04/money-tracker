# Money Tracker v1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Web app (phone + desktop) that replaces the personal finance Excel: quincenas with lines, templates, card installments, auto cuotas line, and carry-over (arrastre) of unpaid lines on close.

**Architecture:** Next.js App Router is both UI and backend (Server Actions). All money math lives in pure modules (`src/lib/*`) with unit tests; DB orchestration (create/close/reopen period, sync cuotas lines) lives in `src/server/*` with integration tests against a Docker Postgres test database. Single-user password login via signed cookie checked in `src/proxy.ts`.

**Tech Stack:** Next.js 16 (App Router, `proxy.ts`), TypeScript, Tailwind CSS, Drizzle ORM + drizzle-kit + `pg`, PostgreSQL (user's existing Docker container `postgres`, v18, locally; Neon in prod), Vitest, Vercel.

**Spec:** `docs/superpowers/specs/2026-11-17-money-tracker-design.md`

## Global Constraints

- UI copy in Spanish. Currency CRC, displayed `₡ 1.234,56` via `Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC' })`.
- DB money columns `numeric(14,2)`. In TypeScript, all money math is done in **integer céntimos** (`number`), converted only at the DB boundary (`toCents` / `fromCents`).
- Period label: `"I Quincena Octubre"` / `"II Quincena Octubre"` (Spanish month names, capitalized).
- Categories: `ingreso | servicio | gasto | ahorro | deuda_fija`. "Egreso" = every category except `ingreso`.
- "Por terminar" threshold = 3 remaining installments (`POR_TERMINAR_THRESHOLD = 3` constant).
- Monthly amounts split across halves: half 1 gets `Math.ceil(cents/2)`, half 2 gets `Math.floor(cents/2)` (the two always sum to the monthly amount).
- Env vars: `DATABASE_URL`, `APP_PASSWORD`, `SESSION_SECRET`. Tests use `TEST_DATABASE_URL`.
- Single user: no `user_id` columns.
- No new dependencies beyond: next, react, tailwindcss, drizzle-orm, drizzle-kit, pg, @types/pg, vitest, dotenv.

## Review Focus

1. **Odd céntimos when splitting/dividing** — monthly ₡23,35 split must give 11,68 + 11,67; Tasa 0 balance must use the unrounded cuota (Reloj faltante 132.511,31, not 132.511,30). Tests in Task 3 and Task 4.
2. **Closing a quincena with nothing unpaid** — must close without creating an empty "Arrastre" line. Test in Task 7.
3. **Reopening after the arrastre line was already paid** — must refuse (error `ARRASTRE_PAGADO`) instead of silently un-carrying money that was paid. Test in Task 7.
4. **Double tap on "Nueva quincena" / "Cerrar quincena"** (slow phone network) — must not create duplicate periods or duplicate arrastre lines; unique constraint + transaction + "already closed" guard. Tests in Task 6 and Task 7.
5. **Amounts typed on a phone keyboard** — `"5.900"`, `"5.900,50"`, `"5900,5"`, `"5900.5"`, `"₡ 10.865"` must parse correctly; garbage returns `null` and the form shows an error. Test in Task 3.

---

## File Structure

```
.env.example                  DATABASE_URL, TEST_DATABASE_URL, APP_PASSWORD, SESSION_SECRET
drizzle.config.ts
vitest.config.ts
src/db/schema.ts              tables + enums
src/db/index.ts               drizzle client (getDb(url?))
src/lib/money.ts              toCents, fromCents, formatCRC, parseAmount, splitMonthly
src/lib/periods.ts            PeriodKey, periodLabel, nextPeriod, prevMonth, monthKey
src/lib/installments.ts       cuota, saldoFaltante, status, advance
src/lib/finance.ts            summarize, expandTemplates, cuotasLineAmount
src/server/auth.ts            sign/verify session, login/logout actions, requireSession
src/server/periods.ts         createPeriod, createNextPeriod, closePeriod, reopenPeriod, syncCuotasLines
src/server/actions.ts         form Server Actions (lines, installments, templates, cards)
src/proxy.ts                  redirect to /login without valid session
src/app/login/page.tsx
src/app/page.tsx              redirects to latest period or shows "Crear primera quincena"
src/app/q/[year]/[month]/[half]/page.tsx   Quincena screen
src/app/cuotas/page.tsx
src/app/plantillas/page.tsx
src/app/manifest.ts + public/icon-192.png, public/icon-512.png
src/components/*              Nav, SummaryCards, CategoryBlock, LineForm, ArrastreDetail, InstallmentForm, TemplateForm
tests/lib/*.test.ts           unit tests
tests/server/*.test.ts        integration tests (TEST_DATABASE_URL)
README.md                     run locally + deploy (Neon + Vercel)
```

---

### Task 1: Scaffold project and test runner

**Files:**
- Create: Next.js app at repo root (via create-next-app), `.env.example`, `vitest.config.ts`, `tests/smoke.test.ts`
- Uses (already exists, do NOT recreate): Docker container `postgres` (PostgreSQL 18, `localhost:5432`, user `postgres`) with databases `money_tracker` and `money_tracker_test`. `.env.local` is written by the user (contains their password); never print or commit it.
- Keep: `IDEAS.md`, `docs/`

**Interfaces:**
- Produces: `npm test` (vitest run), `npm run dev`.

- [ ] **Step 1:** `git init` and create the app in place: `npx create-next-app@latest . --ts --tailwind --eslint --app --src-dir --import-alias "@/*" --use-npm` (answer yes to keep existing files). Confirm `IDEAS.md` and `docs/` still exist.
- [ ] **Step 2:** `.env.example` (placeholders only):
  ```
  DATABASE_URL=postgres://postgres:CONTRASEÑA@localhost:5432/money_tracker
  TEST_DATABASE_URL=postgres://postgres:CONTRASEÑA@localhost:5432/money_tracker_test
  APP_PASSWORD=cambiar
  SESSION_SECRET=una-cadena-larga-aleatoria
  ```
  Ask the user to create `.env.local` from it with their real password (URL-encode special characters, e.g. `%` → `%25`, `$` → `%24`). Wait until it exists.
- [ ] **Step 3:** Confirm `.env.local` is listed in `.gitignore` (create-next-app ignores `.env*`).
- [ ] **Step 4:** `npm i drizzle-orm pg dotenv && npm i -D drizzle-kit @types/pg vitest`. `vitest.config.ts`: node environment, alias `@` → `src`, `setupFiles` loads `dotenv/config` with path `.env.local`, `fileParallelism: false` (integration tests share one DB). Add scripts `"test": "vitest run"`, `"db:generate": "drizzle-kit generate"`, `"db:migrate": "drizzle-kit migrate"`.
- [ ] **Step 5:** `tests/smoke.test.ts`: `expect(1 + 1).toBe(2)`. Run `npm test`. Expected: 1 passed. Run `docker exec postgres psql -U postgres -lqt | cut -d'|' -f1 | grep money_tracker_test`. Expected: prints `money_tracker_test`.
- [ ] **Step 6:** Commit: `git add -A && git commit -m "chore: scaffold Next.js app, Docker Postgres, Vitest"` (confirm `.env.local` is ignored).

---

### Task 2: Database schema and migrations

**Files:**
- Create: `src/db/schema.ts`, `src/db/index.ts`, `drizzle.config.ts`, `drizzle/` (generated), `tests/helpers/db.ts`, `tests/server/schema.test.ts`

**Interfaces:**
- Produces (from `src/db/schema.ts`): tables `periods`, `lines`, `templates`, `cards`, `installments`; enums `categoryEnum`, `sourceEnum`, `frequencyEnum`, `installmentTypeEnum`; types `Period`, `Line`, `Template`, `Card`, `Installment` (`$inferSelect`).
- Produces (from `src/db/index.ts`): `getDb(url = process.env.DATABASE_URL): NodePgDatabase<typeof schema>` (cached per URL), `type Db`.
- Produces (from `tests/helpers/db.ts`): `testDb(): Db` (uses `TEST_DATABASE_URL`, runs migrations once), `resetDb(db): Promise<void>` (truncates all tables `RESTART IDENTITY CASCADE`).

Columns (spec §5; money = `numeric(14,2)`):
- `periods`: `id serial pk, year int, month int (1–12), half int (1|2), closed boolean default false, created_at timestamptz default now()`; **unique (year, month, half)**.
- `lines`: `id serial pk, period_id → periods (cascade), category, name text, estimated numeric not null default 0, actual numeric null, paid boolean default false, paid_at timestamptz null, source default 'manual', template_id → templates (set null) null, card_id → cards (set null) null, carried_to_line_id → lines (set null) null, sort int default 0`. Index on `period_id`, on `carried_to_line_id`. Partial unique index `(period_id, card_id) where source = 'cuotas'`.
- `templates`: `id, name, category, estimated numeric, frequency, half int null, active boolean default true`.
- `cards`: `id, name text unique`.
- `installments`: `id, card_id → cards (restrict), merchant, item, total_installments int, paid_installments int default 0, type, initial_balance numeric, installment_amount numeric null, balance_override numeric null, cancel_soon boolean default false, last_advanced_month text not null (format 'YYYY-MM'), created_at timestamptz default now()`.

- [ ] **Step 1: Write the failing test** `tests/server/schema.test.ts` → `it('rejects a duplicate period')`: insert `{year:2026, month:10, half:1}` twice; expect second insert to reject.
- [ ] **Step 2:** Run `npm test -- schema`. Expected: FAIL (cannot import `@/db/schema`).
- [ ] **Step 3:** Implement schema, `getDb`, `drizzle.config.ts` (dialect postgresql, schema `./src/db/schema.ts`, out `./drizzle`, url `DATABASE_URL`), test helpers (`migrate` from `drizzle-orm/node-postgres/migrator` with `migrationsFolder: 'drizzle'`). Run `npm run db:generate && npm run db:migrate`.
- [ ] **Step 4:** Run `npm test -- schema`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: database schema and migrations`.

---

### Task 3: Money and period helpers (pure)

**Files:**
- Create: `src/lib/money.ts`, `src/lib/periods.ts`, `tests/lib/money.test.ts`, `tests/lib/periods.test.ts`

**Interfaces:**
- Produces (`money.ts`): `toCents(v: string | number | null): number | null`, `fromCents(c: number): string` (e.g. `"10865.00"`), `formatCRC(c: number): string`, `parseAmount(input: string): number | null` (returns céntimos), `splitMonthly(cents: number, half: 1 | 2): number`.
- Produces (`periods.ts`): `type PeriodKey = { year: number; month: number; half: 1 | 2 }`, `periodLabel(p: PeriodKey): string`, `nextPeriod(p: PeriodKey): PeriodKey`, `monthKey(year: number, month: number): string` (`'2026-10'`), `prevMonthKey(year: number, month: number): string`, `comparePeriods(a, b): number`.

- [ ] **Step 1: Write failing tests:**
  ```ts
  // money.test.ts
  expect(toCents('10865.00')).toBe(1086500)
  expect(fromCents(1086500)).toBe('10865.00')
  expect(splitMonthly(2335, 1)).toBe(1168); expect(splitMonthly(2335, 2)).toBe(1167)
  expect(splitMonthly(10001300, 1) + splitMonthly(10001300, 2)).toBe(10001300)
  expect(parseAmount('5.900')).toBe(590000)
  expect(parseAmount('5.900,50')).toBe(590050)
  expect(parseAmount('5900,5')).toBe(590050)
  expect(parseAmount('5900.5')).toBe(590050)
  expect(parseAmount('₡ 10.865')).toBe(1086500)
  expect(parseAmount('1.234.567,89')).toBe(123456789)
  expect(parseAmount('abc')).toBeNull(); expect(parseAmount('')).toBeNull()
  expect(formatCRC(1086500).replace(/\s/g, ' ')).toMatch(/₡\s?10[.\s]865,00/)
  // periods.test.ts
  expect(periodLabel({year:2026,month:10,half:1})).toBe('I Quincena Octubre')
  expect(periodLabel({year:2026,month:10,half:2})).toBe('II Quincena Octubre')
  expect(nextPeriod({year:2026,month:10,half:1})).toEqual({year:2026,month:10,half:2})
  expect(nextPeriod({year:2026,month:12,half:2})).toEqual({year:2027,month:1,half:1})
  expect(prevMonthKey(2027, 1)).toBe('2026-12')
  ```
- [ ] **Step 2:** `npm test -- lib/`. Expected: FAIL (modules missing).
- [ ] **Step 3:** Implement. `parseAmount` rule: strip `₡` and spaces; if it contains `,` → `,` is decimal and `.` are thousands; else if it matches `^\d{1,3}(\.\d{3})+$` → dots are thousands; else `.` is decimal. Reject anything not matching `^\d+(\.\d{1,2})?$` after normalizing. Round to céntimos with integer math (no float drift: split integer/fraction strings).
- [ ] **Step 4:** `npm test -- lib/`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: money and period helpers`.

---

### Task 4: Installment calculations (pure)

**Files:**
- Create: `src/lib/installments.ts`, `tests/lib/installments.test.ts`

**Interfaces:**
- Consumes: céntimos convention from Task 3.
- Produces:
  ```ts
  type InstallmentInput = { type: 'tasa0' | 'minicuotas'; totalInstallments: number; paidInstallments: number;
    initialBalance: number; installmentAmount: number | null; balanceOverride: number | null } // céntimos
  type InstallmentStatus = 'activa' | 'por_terminar' | 'terminada'
  POR_TERMINAR_THRESHOLD = 3
  cuota(i: InstallmentInput): number
  saldoFaltanteCalculado(i: InstallmentInput): number
  saldoFaltante(i: InstallmentInput): { value: number; calculated: number; diff: number | null } // override wins; diff = override - calculated
  installmentStatus(i: InstallmentInput): InstallmentStatus
  advanceInstallment(paid: number, total: number, lastAdvanced: string, targetMonth: string): { paid: number; lastAdvanced: string }
  ```

- [ ] **Step 1: Write failing tests** (real Excel rows):
  - Reloj tasa0 26502262 / 24, paid 12 → `cuota` 1104261, `saldoFaltanteCalculado` 13251131.
  - iPhone tasa0 76918464 / 24, paid 22 → cuota 3204936, faltante 6409872.
  - Operación Camilo minicuotas cuota 2822220, total 24, paid 3 → faltante 59266620.
  - Microondas minicuotas cuota 594910, total 24, paid 0 → faltante 14277840.
  - Override: ExtremeTech minicuotas 2546500×24, paid 10, override 29438200 → `{ value: 29438200, calculated: 35651000, diff: -6212800 }`.
  - Status: Dashcam paid 12/12 → `terminada`; Wallet 17/18 → `por_terminar`; Apple Teclado 23/24 → `por_terminar`; remaining exactly 3 → `por_terminar`; remaining 4 → `activa`.
  - Advance: `(12, 24, '2026-09', '2026-10')` → `{paid:13, lastAdvanced:'2026-10'}`; same call again with `lastAdvanced:'2026-10'` → unchanged (idempotent); `(12, 24, '2026-08', '2026-10')` → paid 14 (skipped month); `(23, 24, '2026-08', '2026-10')` → paid 24 (capped); target earlier than last → unchanged; across year `(5, 12, '2026-11', '2027-01')` → paid 7.
- [ ] **Step 2:** `npm test -- installments`. Expected: FAIL.
- [ ] **Step 3:** Implement. Tasa 0 faltante = `Math.round(initial * (total - paid) / total)` (uses the unrounded cuota, matching Excel). Minicuotas faltante = `cuota * max(total - paid, 0)`. Never negative.
- [ ] **Step 4:** `npm test -- installments`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: installment calculations`.

---

### Task 5: Quincena math (pure): summary, templates, cuotas line

**Files:**
- Create: `src/lib/finance.ts`, `tests/lib/finance.test.ts`

**Interfaces:**
- Consumes: `splitMonthly` (Task 3), `cuota`, `InstallmentInput` (Task 4).
- Produces:
  ```ts
  type Category = 'ingreso' | 'servicio' | 'gasto' | 'ahorro' | 'deuda_fija'
  type LineLike = { category: Category; estimated: number; actual: number | null; paid: boolean; carried: boolean } // carried = carried_to_line_id != null
  type CategoryTotals = { estimated: number; actual: number }
  type Summary = { ingreso: number; egreso: number; disponible: number; faltaPorPagar: number; byCategory: Record<Category, CategoryTotals> }
  lineValue(l: LineLike): number            // actual ?? estimated
  summarize(lines: LineLike[]): Summary
  type TemplateLike = { id: number; name: string; category: Category; estimated: number;
    frequency: 'quincenal' | 'mensual_dividido' | 'mensual_en_quincena'; half: 1 | 2 | null; active: boolean }
  expandTemplates(templates: TemplateLike[], half: 1 | 2): { templateId: number; name: string; category: Category; estimated: number }[]
  cuotasLineAmount(installments: InstallmentInput[], half: 1 | 2): number // Σ cuota where paid < total, then splitMonthly
  ```

- [ ] **Step 1: Write failing tests:**
  - Excel quincena (I Quincena Octubre): ingreso 557.632; servicios 10.865, 16.146, 68.460, 45.000 paid; ahorro 32.580 paid; deudas 120.000, 100.013, 69.132 paid; gastos 5.900, 15.000, 10.000 unpaid + Walmart 0 unpaid → `egreso` 49309600, `disponible` 6453600, `faltaPorPagar` 3090000, `byCategory.servicio.actual` 14047100.
  - Carried lines excluded: same set with the three unpaid gastos marked `carried: true` → `egreso` 46219600, `faltaPorPagar` 0.
  - `ingreso` lines never count in `faltaPorPagar` even if unpaid.
  - `expandTemplates`: quincenal 1000000 → 1000000 in both halves; mensual_dividido 2335 → 1168 (half 1), 1167 (half 2); mensual_en_quincena half 2 → absent in half 1, present in half 2; inactive → absent.
  - `cuotasLineAmount`: Reloj (cuota 1104261, 12/24) + Dashcam (terminada, 12/12) + Wallet (251460, 17/18) → half 1 = `splitMonthly(1355721, 1)` = 677861, half 2 = 677860.
- [ ] **Step 2:** `npm test -- finance`. Expected: FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** `npm test -- finance`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: quincena summary, template expansion, cuotas line`.

---

### Task 6: Create periods (DB): templates, installment advance, cuotas lines

**Files:**
- Create: `src/server/periods.ts`, `tests/server/periods-create.test.ts`

**Interfaces:**
- Consumes: `Db`, schema (Task 2); `nextPeriod`, `prevMonthKey`, `PeriodKey` (Task 3); `advanceInstallment`, `InstallmentInput` (Task 4); `expandTemplates`, `cuotasLineAmount` (Task 5).
- Produces:
  ```ts
  createPeriod(db: Db, key: PeriodKey): Promise<Period>      // idempotent: returns existing if (year,month,half) exists
  createNextPeriod(db: Db): Promise<Period>                   // next after the latest period; throws 'NO_PERIODS' if none
  syncCuotasLines(db: Db, periodId?: number): Promise<void>   // all open periods if no id
  toInstallmentInput(row: Installment): InstallmentInput      // DB row → céntimos
  ```
  Cuotas line: `category 'deuda_fija'`, `source 'cuotas'`, `name = 'Cuotas ' + card.name`, `card_id`, `estimated = actual = cuotasLineAmount(card's installments, half)`.

`createPeriod` runs in one transaction: insert period (on conflict do nothing → return existing without re-adding lines); if `half === 1`, advance every installment with `advanceInstallment(paid, total, last, prevMonthKey(year, month))`; insert lines from `expandTemplates` (source `plantilla`, `template_id` set); then `syncCuotasLines(tx, period.id)`.

`syncCuotasLines` per open period and per card with installments: if no cuotas line → insert (skip if amount 0); if line exists and `paid = false` → update `estimated` and `actual`; if `paid = true` or period closed → leave untouched.

- [ ] **Step 1: Write failing tests** (each test starts with `resetDb`):
  - `createPeriod` with templates (Kolbi quincenal 1086500; Banco Popular mensual_dividido 24000000; CONAPE mensual_en_quincena half 1 6846000) for 2026-11 half 1 → lines Kolbi 1086500, Banco Popular 12000000, CONAPE 6846000, all `source 'plantilla'`, `paid false`.
  - Same for half 2 → no CONAPE line; Banco Popular 12000000.
  - Called twice with the same key (simulates double tap) → one period, no duplicated lines.
  - Installment Reloj (12/24, last '2026-10'): creating 2026-11 half 1 → unchanged at 12 (target `prevMonthKey(2026,11)` = '2026-10' already reached); creating 2026-12 half 1 → paid 13, last '2026-11'; creating 2026-12 half 2 → still 13.
  - Card "TC BAC" with Reloj (cuota 1104261) → period gets one `Cuotas TC BAC` line of 552131 (half 1).
  - `syncCuotasLines` after adding Wallet (251460) → unpaid cuotas line updates to `splitMonthly(1355721,1)` = 677861; if the line was marked paid first → stays 552131.
  - `createNextPeriod` after 2026-12 half 2 → 2027-01 half 1.
- [ ] **Step 2:** `npm test -- periods-create`. Expected: FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** `npm test -- periods-create`. Expected: PASS.
- [ ] **Step 5:** Commit `feat: create quincenas from templates with cuotas line`.

---

### Task 7: Close / reopen period with arrastre (DB)

**Files:**
- Modify: `src/server/periods.ts`
- Create: `tests/server/periods-close.test.ts`

**Interfaces:**
- Consumes: Task 6 functions; `periodLabel`, `nextPeriod`; `lineValue`.
- Produces:
  ```ts
  closePreview(db: Db, periodId: number): Promise<{ lines: Line[]; total: number }>  // unpaid, uncarried egreso lines
  closePeriod(db: Db, periodId: number): Promise<{ arrastreLineId: number | null }>
  reopenPeriod(db: Db, periodId: number): Promise<void>  // throws Error('ARRASTRE_PAGADO') if the arrastre line is paid
  getArrastreDetail(db: Db, lineId: number): Promise<Line[]>  // lines whose carried_to_line_id = lineId
  ```

`closePeriod` in one transaction: lock period row (`for update`); if already closed → return `{ arrastreLineId: null }` (no-op); collect unpaid, uncarried egreso lines; set `closed = true`; if none → done. Else `createPeriod(tx, nextPeriod(key))`, insert one line `{ category 'deuda_fija', source 'arrastre', name 'Arrastre ' + periodLabel(key), estimated = actual = Σ lineValue, paid false }`, set `carried_to_line_id` on the originals.

`reopenPeriod` in one transaction: find the arrastre line via originals' `carried_to_line_id`; if it is paid → throw `ARRASTRE_PAGADO`; else delete it, clear `carried_to_line_id` on originals, set `closed = false`.

- [ ] **Step 1: Write failing tests:**
  - Period with gastos 590000, 1500000, 1000000 unpaid + Kolbi paid → `closePeriod` creates in next period one line `'Arrastre I Quincena Octubre'` with 3090000, `deuda_fija`, `source 'arrastre'`; originals have `carried_to_line_id`; `getArrastreDetail` returns the 3 gastos.
  - Unpaid line with `actual null, estimated 500000` contributes 500000.
  - Nothing unpaid → `closed = true`, `arrastreLineId null`, next period gets no arrastre line.
  - `closePeriod` called twice → exactly one arrastre line.
  - Next period already existed (created from templates) → arrastre line added to it, templates not duplicated.
  - Chain: arrastre line left unpaid in period 2, period 2 closed → period 3 has an arrastre line of the same total whose detail is the period-2 arrastre line, whose detail is the 3 gastos.
  - `reopenPeriod` with unpaid arrastre → arrastre line deleted, originals uncarried, `closed false`.
  - `reopenPeriod` with paid arrastre → rejects `ARRASTRE_PAGADO`, nothing changes.
- [ ] **Step 2:** `npm test -- periods-close`. Expected: FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** `npm test`. Expected: all PASS.
- [ ] **Step 5:** Commit `feat: close and reopen quincena with arrastre`.

---

### Task 8: Password login and route protection

**Files:**
- Create: `src/server/auth.ts`, `src/proxy.ts`, `src/app/login/page.tsx`, `tests/server/auth.test.ts`

**Interfaces:**
- Produces:
  ```ts
  SESSION_COOKIE = 'mt_session'
  signSession(secret: string, now?: number): string          // `${expiresAtMs}.${hmacSha256Hex(expiresAtMs)}`, 90 days
  verifySession(secret: string, token: string | undefined, now?: number): boolean
  loginAction(prev: { error?: string } | undefined, form: FormData): Promise<{ error?: string }>  // 'use server'
  logoutAction(): Promise<void>
  requireSession(): Promise<void>   // throws/redirects to /login; call at the top of every Server Action
  ```
  Cookie: httpOnly, `secure` in production, `sameSite 'lax'`, `maxAge` 90 days. Password compare with `crypto.timingSafeEqual` on equal-length SHA-256 digests.

- [ ] **Step 1: Write failing tests:** valid token verifies; tampered signature → false; expired (`now` past expiry) → false; `undefined` → false; token signed with another secret → false.
- [ ] **Step 2:** `npm test -- auth`. Expected: FAIL.
- [ ] **Step 3:** Implement with `node:crypto`. `src/proxy.ts` (`export default async function proxy(req)`) redirects to `/login` when `verifySession` fails, for every path except `/login`, `/manifest.webmanifest`, `/icon-*`, `/_next/*`, `favicon.ico`. Login page: single password field (`type="password"`, `autoComplete="current-password"`, label "Contraseña"), button "Entrar", shows "Contraseña incorrecta" on error; on success redirect `/`.
- [ ] **Step 4:** `npm test -- auth` → PASS. Then `npm run dev`; `curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' localhost:3000/` → `307 http://localhost:3000/login`.
- [ ] **Step 5:** Commit `feat: password login and route protection`.

---

### Task 9: Quincena screen

**Files:**
- Create: `src/app/page.tsx`, `src/app/q/[year]/[month]/[half]/page.tsx`, `src/server/actions.ts` (line + period actions), `src/components/Nav.tsx`, `SummaryCards.tsx`, `CategoryBlock.tsx`, `LineForm.tsx`, `ArrastreDetail.tsx`, `ClosePeriodDialog.tsx`
- Modify: `src/app/layout.tsx` (lang `es`, Nav: Quincena · Cuotas · Plantillas · Salir)

**Interfaces:**
- Consumes: `createPeriod`, `createNextPeriod`, `closePreview`, `closePeriod`, `reopenPeriod`, `getArrastreDetail`, `syncCuotasLines` (Tasks 6–7); `summarize`, `lineValue` (Task 5); `formatCRC`, `parseAmount`, `toCents`, `fromCents` (Task 3); `periodLabel`, `nextPeriod` (Task 3); `requireSession` (Task 8).
- Produces (Server Actions in `actions.ts`, each starts with `await requireSession()` and ends with `revalidatePath`): `addLine(form)`, `updateLine(form)`, `deleteLine(form)`, `togglePaid(form)` (sets/clears `paid_at`), `createFirstPeriodAction(form)` (year, month, half), `createNextPeriodAction()`, `closePeriodAction(form)`, `reopenPeriodAction(form)` (returns `{ error: 'No se puede reabrir: el arrastre ya está pagado' }` on `ARRASTRE_PAGADO`).

Behavior (spec §7 sketch):
- `/` → redirect to latest period's URL; if none, form "Crear primera quincena" (año, mes, I/II).
- Header: period label, ◀ ▶ to adjacent existing periods, badge "Cerrada" if closed.
- Summary cards: Ingreso, Egreso, Disponible (red if negative), Falta por pagar.
- Blocks in Excel order: Ingresos, Servicios, Gastos, Ahorros, Deudas fijas; each row: paid toggle (✔/✘, big tap target ≥ 44px), name, estimated, actual, diff (actual − estimated) shown only when ≠ 0; block footer totals estimated/actual. Carried lines shown greyed with "↪ arrastrado" and not toggleable. Cuotas lines show "auto". Arrastre lines expand (`<details>`) to `ArrastreDetail` (recursive).
- "+ Agregar" per block opens `LineForm` (name, aproximado, real, pagado); amounts parsed with `parseAmount`, invalid → inline error "Monto inválido". `inputMode="decimal"` on amount fields.
- Buttons: "Nueva quincena" (→ navigates to created period), "Cerrar quincena" opens `ClosePeriodDialog` listing `closePreview` lines + total with confirm; closed period shows "Reabrir".
- Buttons disable while pending (`useFormStatus`).
- Mobile first: single column; two columns (`md:`) for blocks like the Excel.

- [ ] **Step 1:** Implement page, components, actions.
- [ ] **Step 2:** Verify in browser (`npm run dev`, phone-width 390px and desktop): log in, create I Quincena Noviembre 2026, add the Excel quincena's lines, check summary shows Disponible ₡64.536,00 and Falta por pagar ₡30.900,00; close it → next quincena has "Arrastre I Quincena Noviembre" ₡30.900,00 whose detail lists the 3 gastos; reopen → arrastre gone. Type `5.900` in an amount → saved as ₡5.900,00.
- [ ] **Step 3:** `npm test && npm run build`. Expected: tests pass, build succeeds.
- [ ] **Step 4:** Commit `feat: quincena screen`.

---

### Task 10: Cuotas screen and cards

**Files:**
- Create: `src/app/cuotas/page.tsx`, `src/components/InstallmentForm.tsx`
- Modify: `src/server/actions.ts`

**Interfaces:**
- Consumes: `cuota`, `saldoFaltante`, `installmentStatus`, `toInstallmentInput` (Tasks 4, 6); `syncCuotasLines` (Task 6).
- Produces actions: `addCard(form)`, `addInstallment(form)`, `updateInstallment(form)`, `deleteInstallment(form)`, `toggleCancelSoon(form)`. Every installment mutation calls `syncCuotasLines(db)` before revalidating.

Behavior:
- Per card: rows grouped Activas (blue) / Por terminar (yellow) / Terminadas (green, collapsed); cancel_soon rows also tinted yellow with a "Cancelar pronto" tag. Columns: Comercio, Artículo, Cuotas `paid/total`, Forma pago, Saldo inicial, Cuota, Saldo faltante (with "Δ vs calculado" when override set).
- Footer per card: Σ cuota mensual of non-finished, Σ saldo faltante, and "por quincena" (= monthly split).
- Form: comercio, artículo, total cuotas, "Cuotas pagadas (hasta el mes pasado)", forma de pago (Tasa 0 / Minicuotas), saldo inicial, monto cuota (required only for Minicuotas), saldo real del estado de cuenta (optional). New installments get `last_advanced_month = prevMonthKey(current year, current month)`. Validation: total ≥ 1, 0 ≤ paid ≤ total, amounts parse.

- [ ] **Step 1:** Implement.
- [ ] **Step 2:** Verify in browser: enter Reloj, Operación Camilo, Wallet, Dashcam from the Excel; values match (Reloj faltante ₡132.511,31; Camilo ₡592.666,20; Dashcam under Terminadas; Wallet under Por terminar). Open the current quincena → "Cuotas TC BAC" line equals the card's "por quincena" figure.
- [ ] **Step 3:** `npm test && npm run build` → pass.
- [ ] **Step 4:** Commit `feat: cuotas screen`.

---

### Task 11: Plantillas screen

**Files:**
- Create: `src/app/plantillas/page.tsx`, `src/components/TemplateForm.tsx`
- Modify: `src/server/actions.ts`

**Interfaces:**
- Produces actions: `addTemplate(form)`, `updateTemplate(form)`, `toggleTemplate(form)`, `deleteTemplate(form)`.

Behavior: list by category; columns Nombre, Monto, Frecuencia ("Cada quincena" / "Mensual ÷ 2" / "Mensual en I|II quincena"), Activa. Form validates `half` required only for `mensual_en_quincena`. Note under title: "Se aplican al crear una quincena nueva."

- [ ] **Step 1:** Implement.
- [ ] **Step 2:** Verify in browser: create Kolbi (cada quincena), Banco Popular (mensual ÷ 2), then "Nueva quincena" → both lines present with expected amounts.
- [ ] **Step 3:** `npm test && npm run build` → pass.
- [ ] **Step 4:** Commit `feat: plantillas screen`.

---

### Task 12: PWA manifest, README, deploy

**Files:**
- Create: `src/app/manifest.ts`, `public/icon-192.png`, `public/icon-512.png`, `README.md`

- [ ] **Step 1:** `manifest.ts`: name "Money Tracker", short_name "Finanzas", `start_url '/'`, `display 'standalone'`, theme/background colors, the two icons (simple generated PNGs with "₡").
- [ ] **Step 2:** README (Spanish): run locally (Postgres container running with `money_tracker` and `money_tracker_test` DBs, `cp .env.example .env.local`, `npm i`, `npm run db:migrate`, `npm run dev`, `npm test`); deploy: create Neon project → copy pooled connection string; `DATABASE_URL=<neon> npm run db:migrate`; import repo in Vercel; set `DATABASE_URL`, `APP_PASSWORD`, `SESSION_SECRET` (`openssl rand -hex 32`); on the phone open the URL → "Agregar a pantalla de inicio".
- [ ] **Step 3:** `npm run build` → succeeds; `curl -s localhost:3000/manifest.webmanifest` (with dev server) returns JSON containing `"standalone"`.
- [ ] **Step 4:** Commit `feat: PWA manifest and README`.
- [ ] **Step 5 (with the user):** Deploy to Neon + Vercel together; the user creates the accounts and pastes secrets themselves.
