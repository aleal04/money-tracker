# Money Tracker — Diseño versión Laravel

Fecha: 2026-11-18 · Reemplaza el stack de `2026-11-17-money-tracker-design.md` §4. **Las reglas de negocio (§5 modelo, §6 cálculos, §7 pantallas) de ese documento siguen vigentes**, con las decisiones tomadas durante la versión NestJS (abajo, §6).

## 1. Por qué

El usuario trabaja a diario con Laravel + Inertia + Vue (proyectos `ssi` y `fitness`) y quiere mantener esta app con el mismo stack y las mismas convenciones. La versión NestJS + Vue (rama `feat/v1`) queda como referencia funcional y de diseño visual.

## 2. Decisiones del usuario

- **Laravel + Inertia + Vue 3 como SPA** (un solo proyecto; sin `api/` ni `web/` separados).
- **Arquitectura = la de `ssi`/`fitness`** (ssi es la referencia; ver §4).
- **MySQL** (contenedor Docker `mysql` local) con **migraciones de Laravel**.
- **Sin login por ahora.** ⚠️ Riesgo: cualquiera con la URL ve y edita todo. Antes de publicar en internet hay que agregar autenticación.
- **Mismo repo, rama nueva** `laravel` desde `main`. `feat/v1` (NestJS) queda intacta como respaldo.

## 3. Stack (igual que ssi/fitness)

- Laravel 12, PHP ^8.2, `inertiajs/inertia-laravel` 3, `@inertiajs/vue3` 3, Vue 3.5 + TypeScript, Vite 7, pnpm.
- **SCSS** (`resources/scss/{ui,components,pages}`, sin `<style>` en `.vue`), sin Tailwind. Se porta el diseño aprobado: crema/teal/amarillo, sidebar fijo (rail de iconos en celular), topbar con modo oscuro + "Mi cuenta", Inicio dashboard, pestañas por categoría en Quincena, tablas.
- Font Awesome (`@fortawesome/vue-fontawesome`, `<font-awesome-icon>`).
- Tests: PHPUnit 11 (Feature + Unit) con **sqlite en memoria**; Vitest + @vue/test-utils + happy-dom para composables/componentes clave.
- Calidad: Pint (`preset laravel`, imports ordenados por largo), Biome, `.githooks/pre-push` (phpunit, vitest, lint, types).
- `CLAUDE.md` del proyecto con las reglas de ssi aplicables (responder en español; código/PHPDoc en inglés; PHPDoc en cada método; no cambiar dependencias sin aprobación; no commit/push sin pedirlo).

## 4. Arquitectura (copia de ssi)

```
Route (routes/<dominio>.php) → XxxController (implements XxxControllerContract)
  → StoreXxxFormRequest → XxxStoreDto::fromRequest (readonly)
  → XxxService (implements XxxServiceContract)
      → XxxRepository (escrituras, runTransactionSteps, try/catch → ReturnResponse)
      → XxxQueryRepository (lecturas, extends BaseQueryRepository)
  → XxxModel
```

- Carpetas: `app/{Constant,Contracts/{Controllers,Services}/<D>,DTOs/<D>,Http/{Controllers,Requests}/<D>,Models/<D>,Repositories/{Concerns,<D>},Services/<D>,Helpers}`.
- **Prohibido** (regla de ssi): `app/Actions`, `app/Data`, `app/Http/Resources`, `app/Rules`. Constantes en `app/Constant/*Constant.php` en vez de Enums.
- Utilidades copiadas/adaptadas de ssi: `Helpers/ReturnResponse.php`, `Repositories/Concerns/RunsTransactionStepsTrait.php`, `Repositories/BaseQueryRepository.php`.
- Sin auth/permission middleware (no hay login). No se copian Okta, permisos, PDF, Excel, mail, log-viewer.
- Dominios: **Period** (quincenas + líneas + cierre/arrastre), **Installment** (tarjetas + cuotas), **Template** (plantillas), **Dashboard** (Inicio, solo lectura).
- Las reglas de dinero viven en los Services (y helpers puros testeables): todo en **céntimos enteros** en PHP; `decimal(14,2)` en MySQL.
- Frontend: `resources/js/{Pages/<D>,Layouts/AppLayout.vue,Components/{UI,Pages/<D>},Composables/{shared,<D>},Constants,Types}`. Index pages via `Inertia::render` y listas por JSON desde composables (patrón ssi); formularios por composables + axios/router con errores de FormRequest mostrados bajo cada campo.
- Rutas kebab-case, un archivo por dominio, `->whereNumber()`, PUT para update, DELETE para destroy.

## 5. Base de datos (MySQL 8.4 + migraciones)

Mismas tablas que §5 del diseño original (`periods`, `lines`, `templates`, `cards`, `installments`), snake_case (convención Laravel; MySQL, no el PascalCase de SQL Server de ssi).
- Unicidad de la línea de cuotas por (quincena, tarjeta): MySQL no tiene índices parciales → columna generada `cuotas_card_id` (= `card_id` si `source='cuotas'`, si no `NULL`) con `UNIQUE(period_id, cuotas_card_id)`.
- "Insertar si no existe" para quincenas: `UNIQUE(year, month, half)` + `insertOrIgnore`/`firstOrCreate` dentro de transacción con lock.
- Tests en sqlite en memoria: las migraciones deben correr también en sqlite (la columna generada se declara con `storedAs`/`virtualAs` compatible, o la regla se valida también en el repositorio).

## 6. Reglas de negocio (sin cambios; resumen de lo decidido en la versión NestJS)

- Quincena: líneas con aproximado/real/pagado; resumen ingreso/egreso/disponible/falta por pagar; totales por categoría sin líneas arrastradas.
- Plantillas: quincenal / mensual ÷ 2 (I = ceil, II = floor) / mensual en I o II.
- Cuotas: Tasa 0 cuota = inicial/total; faltante con cuota sin redondear; Minicuotas faltante = cuota × restantes; override del estado de cuenta con diferencia; estado activa / por terminar (≤ 3) / terminada; avance una vez por mes al crear la I quincena (idempotente; TZ America/Costa_Rica).
- Línea "Cuotas <tarjeta>": se crea con `actual = null`; la sincronización solo cambia el aproximado, solo en quincenas abiertas no pagadas y no anteriores a la última I quincena.
- Cerrar quincena: lo no pagado (egreso) pasa como **una sola línea "Arrastre <quincena>"** en Deudas fijas de la siguiente, con detalle; no se puede cerrar si la siguiente está cerrada; reabrir bloqueado si el arrastre está pagado o ya fue arrastrado de nuevo; borrar un arrastre está prohibido; en quincena cerrada se puede agregar/editar y pagar, no desmarcar un pago.
- Montos escritos como en el Excel/teléfono ("5.900", "5.900,50", "₡ 10.865") se aceptan; formato de salida `₡10.865,00`.
- Casos de prueba: los mismos números reales del Excel (Reloj 132.511,31; Camilo 592.666,20; quincena octubre disponible 64.536, falta 30.900; etc.).

## 7. Fuera de alcance (v1 Laravel)

Login/usuarios, permisos, PDF/Excel/correo, despliegue (se decide después), migrar datos (la base actual está vacía).
