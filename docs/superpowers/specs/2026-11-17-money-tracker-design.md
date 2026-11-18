# Money Tracker — Diseño

Fecha: 2026-11-17 · Ideas de origen: `IDEAS.md`

## 1. Intención

Reemplazar el Excel personal de finanzas (presupuesto por **quincena** + hoja de **compras en cuotas** de tarjeta) por una app web usable desde el celular y la compu.

Por qué: el Excel obliga a copiar la hoja cada quincena, a pasar a mano los montos de cuotas a "Deudas fijas", a recordar los gastos arrastrados, y tiene fórmulas inconsistentes (ver `IDEAS.md`). Además no permite anotar gastos en el momento desde el celular.

Usuario: una sola persona (el dueño). Moneda: CRC (₡).

## 2. Objetivos, no-objetivos, anti-objetivos

**Objetivos (v1)**
1. Quincena con Ingresos y 4 categorías (Servicios, Gastos, Ahorros, Deudas fijas); cada línea con aproximado, real y pagado.
2. Resumen: ingreso total, egreso total, disponible, **falta por pagar**.
3. Crear la siguiente quincena en un clic a partir de **plantillas recurrentes**.
4. Registro de **compras en cuotas** (Tasa 0 / Minicuotas) con cuota y saldo faltante calculados; estados activa / por terminar / terminada + marca manual "cancelar pronto".
5. La línea de **cuotas de tarjeta** en Deudas fijas se calcula sola: suma mensual de cuotas activas ÷ 2 por quincena.
6. **Arrastre**: al cerrar la quincena, lo no pagado pasa como una línea a la siguiente.
7. Usable desde el celular (responsive + instalable como PWA).

**No-objetivos (v1)** — quedan para v2 (ver `IDEAS.md` §3): simulador de cancelación anticipada, préstamos con saldo y fecha de fin, ahorros con meta, recordatorios, gráficos/historial avanzado, importar Excel, USD, conexión con bancos, multiusuario.

**Anti-objetivos** (fracaso aunque funcione):
- Que registrar algo sea más lento que en el Excel.
- Que los totales no cuadren con lo que el usuario sabe que pagó / con el estado de cuenta.

## 3. Enfoques considerados

| Enfoque | Trade-off |
|---|---|
| **A. App web en la nube + login simple** ✅ elegido | Acceso desde cualquier lugar y celular; un poco más de configuración inicial. |
| B. App local en la Mac | Simplísima y privada, pero no sirve para anotar gastos fuera de casa. |
| C. Datos solo en el navegador | Sin servidor, pero no sincroniza entre dispositivos y se pierden datos fácilmente. |

Elegido A por el usuario: registrar gastos desde el celular es la mejora clave frente al Excel.

## 4. Stack (acordado con el usuario)

- **Next.js (App Router) + TypeScript** como frontend y backend (Server Actions), desplegado en **Vercel** (plan gratis).
- **PostgreSQL**: local en el contenedor **Docker** `postgres` del usuario (PostgreSQL 18, bases `money_tracker` y `money_tracker_test`) para desarrollo; **Neon** (plan gratis) en producción. Misma `DATABASE_URL`, distinto valor.
- **Drizzle ORM** + drizzle-kit para esquema y migraciones.
- **Login simple**: una contraseña en variable de entorno (`APP_PASSWORD`) + cookie de sesión firmada; middleware protege todas las rutas. Un solo usuario, sin `user_id` en las tablas.
- Descartado Supabase: el plan gratis pausa el proyecto tras 7 días de poca actividad.
- **Tailwind CSS** para la UI.
- **Vitest** para pruebas de la lógica de cálculo.
- PWA: `manifest` + íconos para "Agregar a pantalla de inicio". Sin modo offline en v1.

## 5. Modelo de datos

Montos en colones con 2 decimales (`numeric(14,2)`).

- **periods** (quincena): `id, year, month, half (1|2), closed (bool)`. Único por (year, month, half). Nombre mostrado: "I Quincena Octubre".
- **lines**: `id, period_id, category (ingreso|servicio|gasto|ahorro|deuda_fija), name, estimated, actual (nullable), paid (bool), paid_at, source (manual|plantilla|cuotas|arrastre), template_id?, card_id?, sort`.
  - `card_id` solo en la línea de **cuotas**: la tarjeta a la que corresponde.
  - `carried_to_line_id` (nullable): si la línea no se pagó y se arrastró, apunta a la **línea única de arrastre** de la quincena siguiente. El detalle de esa línea = las líneas originales que apuntan a ella.
- **templates** (recurrentes): `id, name, category, estimated, frequency (quincenal|mensual_dividido|mensual_en_quincena), half? (1|2 si mensual_en_quincena), active`.
  - `quincenal`: el monto completo en cada quincena.
  - `mensual_dividido`: monto ÷ 2 en cada quincena (así paga el usuario la tarjeta).
  - `mensual_en_quincena`: el monto completo solo en la quincena indicada.
- **cards**: `id, name` (ej. "TC BAC").
- **installments** (compras en cuotas): `id, card_id, merchant, item, total_installments, paid_installments, type (tasa0|minicuotas), initial_balance, installment_amount (nullable para tasa0), balance_override (nullable), cancel_soon (bool), last_advanced_month (YYYY-MM, nullable), created_at`.
- No hay tabla aparte de arrastre: es una línea con `source = arrastre`; su detalle sale de `carried_to_line_id`.

## 6. Reglas de cálculo

Viven en un módulo puro (`lib/finance.ts`) con pruebas unitarias.

- **Cuota Tasa 0** = `initial_balance / total_installments`.
- **Cuota Minicuotas** = `installment_amount` (dato del banco).
- **Saldo faltante**:
  - Tasa 0: `initial_balance − paid_installments × cuota`.
  - Minicuotas: `cuota × (total − pagadas)`.
  - Si hay `balance_override` (saldo real del estado de cuenta), se usa ese y se muestra la diferencia con el calculado.
- **Estado**: terminada si `pagadas ≥ total`; por terminar si quedan ≤ 3 cuotas (configurable); si no, activa. `cancel_soon` es independiente y manual.
- **Línea de cuotas por quincena** (por tarjeta) = `Σ cuota de compras con pagadas < total ÷ 2`, donde `paid_installments` = cuotas pagadas **hasta el mes anterior**. Así una compra en su última cuota sigue en la línea de ese mes. Se regenera solo mientras la quincena esté abierta y la línea no esté pagada; el usuario puede editar el real.
- **Avance de cuotas** (mes calendario): al crear la **I quincena** del mes M, cada compra con pagadas < total y `last_advanced_month < M−1` suma 1 y guarda `last_advanced_month = M−1`. Idempotente: crear, borrar o recrear quincenas no avanza dos veces. Si se salta un mes, avanza 1 por cada mes faltante. El usuario siempre puede corregir `paid_installments` a mano.
- **Gastos durante la quincena**: el usuario anota **todo** gasto en la quincena donde ocurre (en el celular, en el momento), con **Pagado SI/NO** como en el Excel.
- **Cerrar quincena** (botón, lo decide el usuario) — aquí ocurre el **arrastre**:
  1. Muestra una confirmación con la lista de líneas de egreso **no pagadas** y su total.
  2. Crea la quincena siguiente si no existe.
  3. Crea en la siguiente quincena **una sola línea** en Deudas fijas, "Arrastre I Quincena Oct", con `source = arrastre` y monto = Σ de las no pagadas (real, o aproximado si no hay real). Se paga **completa** ahí. Tocándola se despliega el **detalle**: cada línea original con su categoría y monto.
  4. En la quincena cerrada, esas líneas quedan visibles marcadas "↪ arrastrado" y **dejan de contar en su egreso** (la plata no salió ahí; cuenta en la siguiente). Así no se cuenta doble.
  5. Las líneas automáticas (cuotas) dejan de recalcularse. Se puede seguir editando a mano; reabrir la quincena deshace el arrastre de las copias aún no pagadas.
  - Si la línea de arrastre vuelve a quedar sin pagar, se arrastra de nuevo como parte del siguiente arrastre (su detalle sigue accesible).
  - La quincena siguiente se puede crear (con plantillas) antes de cerrar la anterior; el cierre solo le agrega las líneas arrastradas.
- **Resumen de la quincena**:
  - Ingreso = Σ ingresos (real si existe, si no aproximado).
  - Egreso = Σ líneas de egreso no arrastradas (real si existe, si no aproximado).
  - Disponible = Ingreso − Egreso.
  - Falta por pagar = Σ líneas de egreso con `paid = false` (no arrastradas).
  - Diferencia aproximado vs real por línea y por categoría.

## 7. Pantallas

1. **Login** (solo contraseña).
2. **Quincena** (inicio): selector de quincena; tarjetas de resumen (Ingreso, Egreso, Disponible, Falta por pagar); 4 bloques por categoría como en el Excel; marcar pagado con un toque; agregar/editar línea; botón "Nueva quincena". En celular, bloques apilados.
3. **Cuotas**: lista por tarjeta, agrupada por estado (activa / por terminar / terminada) con colores de referencia (azul / amarillo / verde); totales de cuota mensual y saldo faltante; agregar/editar compra; marcar "cancelar pronto".
4. **Plantillas**: CRUD de líneas recurrentes con su frecuencia.

Boceto de la quincena (celular):
```
I Quincena Noviembre        [◀] [▶]
Ingreso  557.632   Egreso   493.096
Disponible 64.536  Falta pagar 30.900
── Servicios ───────────── 140.471
 ✔ Kolbi           10.865
 ✔ Claro           16.146
── Deudas fijas ────────── 289.145
 ✔ TC BAC (cuotas) 100.013  ⓘ auto
 ✘ ↩ Arrastre I Q. Oct  30.900  [▸ detalle]
     · Recibo agua Dpt   5.900
     · Gasolina         15.000
     · Gasolina         10.000
 ✘ Banco Popular  120.000
── Gastos ────────────────  30.900
 ✘ Gasolina        15.000
 ...
[+ Agregar]  [Nueva quincena]  [Cerrar quincena]
```

## 8. Calidad

- Pruebas unitarias de todo `lib/finance.ts`, usando los números reales del Excel como casos (ej. Reloj: 265.022,62 / 24 = 11.042,61; Operación Camilo faltante 592.666,20).
- Formato CRC `₡ 1.234,56` (locale `es-CR`).

## 9. Decisiones

Del usuario:
- Todo gasto se anota en la quincena donde ocurre, con Pagado SI/NO.
- Al cerrar la quincena, **lo no pagado pasa como arrastre** a la siguiente y se paga completo ahí.
- Pagos mensuales (como la tarjeta) se dividen entre las 2 quincenas.

De Claude (corregibles):
- (Usuario) Lo arrastrado pasa como **una sola línea con el total**, con el detalle desplegable.
- Esa línea va en **Deudas fijas**, como el "TC Crédito BAC" de arrastre en el Excel. (Claude.)
- Lo arrastrado deja de contar en el egreso de la quincena cerrada (evita contar doble).
- Avance de cuotas por mes calendario, al crear la I quincena, idempotente.
- "Por terminar" = quedan ≤ 3 cuotas.
- Cerrar es manual, con confirmación; reabrir deshace el arrastre no pagado.

Queda al builder: estructura de carpetas, componentes de UI, migraciones de Drizzle, manejo de errores de formulario.
