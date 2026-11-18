# Espacios y periodos flexibles — Diseño

Fecha: 2026-11-18 · Proyecto: rama `laravel` (Laravel 12 + Inertia + Vue, MySQL por scripts, convenciones ssi).
Complementa `2026-11-18-money-tracker-laravel-design.md`. Todo lo no mencionado aquí sigue igual.

## 1. Intención

Hoy la app administra un solo dinero (el salario) en quincenas fijas. El usuario quiere manejar **varias fuentes de dinero** ("espacios") —p. ej. Salario, Ahorro, Servicios digitales (una cuenta exclusiva para Netflix/Spotify/…)— cada una con su propio ritmo y su saldo que pasa de periodo a periodo, y que la plata que sale de un espacio hacia otro **se conecte sola**.

## 2. Decisiones del usuario

- **Espacios**: cada uno es una "bolsa" de dinero con sus propios periodos.
- **Resumen por periodo**: Saldo anterior + Entró − Gastado = **Restante**; el restante (positivo **o negativo**) pasa como saldo anterior al siguiente periodo del mismo espacio.
- **Transferencias conectadas**: una línea de gasto con destino a otro espacio, al marcarse pagada, aparece sola como ingreso en el espacio destino.
- **Periodos flexibles (opción C)**: cada espacio tiene una **frecuencia** (semanal, quincenal, mensual, personalizada) que **propone** las fechas del siguiente periodo, y el usuario puede **ajustar** inicio y fin.
- Enfoque A (espacios con periodos propios) sobre B (contabilidad de doble partida).

## 3. Decisiones de Claude (corregibles)

1. **Saldo anterior calculado**, no guardado: corregir un periodo viejo ajusta todo lo posterior.
2. **Saldo inicial** por espacio (lo que la cuenta ya tiene hoy); por defecto ₡0. **Meta de ahorro: fuera de alcance** (v2).
3. **Salario también muestra saldo anterior/restante** (todos los espacios funcionan igual). No hay doble conteo con el arrastre: lo arrastrado deja de contar como gastado en el periodo cerrado y cuenta en el siguiente.
4. _(Decidido por el usuario)_ **El destino de la transferencia es el periodo del espacio destino que contiene la fecha de inicio del periodo de origen** (una línea de noviembre entra a noviembre aunque se marque pagada en diciembre); si no existe, se crea según la frecuencia del destino.
5. La línea creada en el destino **no se edita ahí**: se cambia desde la línea origen (pagar/despagar, monto, borrar se propagan).
6. **Tarjetas pertenecen a un espacio** (por defecto el principal) y su línea de cuotas se genera en los periodos de ese espacio.
7. Etiquetas de periodo según frecuencia: quincenal "I Quincena Octubre 2026", mensual "Octubre 2026", semanal "Semana 12–18 nov 2026", personalizada "12 nov – 26 nov 2026" (se agrega el año, pendiente desde la revisión anterior).

## 4. Modelo de datos (convención ssi, MySQL)

- **Space**: IdSpace, Name, Frequency (`weekly|biweekly|monthly|custom`), CustomDays (int, solo `custom`), InitialBalance decimal(14,2), IsPrimary (uno solo), Sort, + auditoría. UQ nombre activo (columna generada como Card).
- **Period**: + FkSpace, **StartDate**, **EndDate** (date). Se eliminan Year/Month/Half. UQ (FkSpace, StartDate) entre activos. Validación: sin traslapes dentro del espacio; EndDate ≥ StartDate.
- **Line**: + **FkTargetSpace** (null = gasto normal), + **FkTransferFromLine** (en la línea de ingreso creada en el destino). Fuente nueva `Source = 'transfer'` para la línea de ingreso del destino.
- **Template**: + FkSpace, + FkTargetSpace (plantilla de transferencia). Frecuencia redefinida (§6).
- **Card**: + FkSpace.
- Datos actuales: se crea el espacio "Salario" (principal, quincenal) y todo lo existente pasa a él. (La base real aún está vacía; se documenta igual el script de ajuste.)

## 5. Periodos

- **Siguiente periodo** de un espacio: empieza el día después del EndDate del último; fin propuesto según frecuencia:
  - `biweekly`: mitades de calendario (1–15, 16–fin de mes) como hoy; si el inicio no coincide (fechas ajustadas), dura 15 días.
  - `monthly`: hasta fin de mes (o 1 mes menos un día si no empieza el día 1).
  - `weekly`: 7 días. `custom`: CustomDays días.
- El usuario puede cambiar inicio/fin al crear o después (si no rompe traslapes y el periodo no tiene arrastre conectado roto).
- "Nueva", "Cerrar", "Reabrir" y arrastre funcionan igual pero **dentro del espacio** y según orden por StartDate.
- **Mes de un periodo** = mes de su StartDate (usado por plantillas y cuotas).

## 6. Plantillas y cuotas con periodos flexibles

- Frecuencias de plantilla:
  - `cada_periodo`: monto completo en cada periodo (antes "quincenal").
  - `mensual_repartido`: monto mensual repartido en partes iguales entre los periodos del espacio que empiezan en ese mes (2 si quincenal, 1 si mensual, 4–5 si semanal; el resto en céntimos va al primer periodo). Para contar los periodos del mes se usa la proyección de la frecuencia.
  - `mensual_dia`: monto completo solo en el periodo que contiene el **día N** del mes (reemplaza "mensual en I/II quincena": I = día 1, II = día 16).
- Cuotas: la cuota mensual de cada tarjeta se reparte igual que `mensual_repartido` entre los periodos del mes en el espacio de la tarjeta. El avance de cuotas ocurre al crear el **primer periodo de un mes nuevo** en ese espacio (idempotente, igual que hoy).

## 7. Saldos

Para un periodo P de un espacio E (orden por StartDate):

- **Saldo anterior** = Restante del periodo anterior de E; para el primero = InitialBalance de E.
- **Entró** = Σ ingresos (real ?? aproximado), incluidas las transferencias recibidas.
- **Gastado** = Σ egresos no arrastrados (real ?? aproximado), incluidas las transferencias enviadas.
- **Restante** = Saldo anterior + Entró − Gastado.
- Se mantienen "Falta por pagar" y totales por categoría.

## 8. Transferencias

- Línea origen: categoría de egreso con FkTargetSpace. Al marcarla **pagada**: crear (o actualizar) en el destino la línea de ingreso `Source='transfer'`, nombre "Desde <espacio origen>: <nombre>", monto = real ?? aproximado, pagada, FkTransferFromLine = línea origen, en el periodo del destino que contiene el **StartDate del periodo de origen** (crearlo si falta).
- **Despagar** la origen → borrar (lógico) la línea destino. **Cambiar monto** de una origen pagada → actualizar destino. **Borrar** la origen → borrar la destino.
- No se permite transferir a sí mismo. Todo en una sola transacción.
- Si el periodo destino está cerrado, la línea igual se crea/actualiza (como agregar a mano en un periodo cerrado) — su restante cambia y se propaga.

- _(Decidido por el usuario)_ **Transferencia sin pagar al cerrar**: no se suma al arrastre general; pasa al siguiente periodo del mismo espacio como **su propia línea** ("↩ <nombre>"), conservando FkTargetSpace, y se marca la original como arrastrada. Al pagar la nueva, llega al destino según la regla anterior (periodo del destino que contiene el StartDate de **su** periodo). Reabrir deshace también estas líneas mientras no estén pagadas (mismas reglas que el arrastre: si ya se pagó o se volvió a arrastrar, no se puede reabrir).

## 8b. Aclaraciones (revisión del diseño)

- **Saldo en vivo**: el saldo anterior se calcula aunque el periodo previo esté abierto (incluye lo aún no pagado como gastado); Inicio muestra el restante del último periodo de cada espacio.
- **Reabrir** no toca transferencias ya pagadas: su línea destino se mantiene.
- **Huecos** entre periodos se permiten (fechas ajustadas); no se permiten traslapes. Si el destino no tiene un periodo para la fecha, se crean los periodos faltantes desde su último periodo según la frecuencia, hasta cubrirla; máximo 12; si hace falta más, o la fecha es anterior al primer periodo del destino, error 409 pidiendo crear el periodo a mano.
- **Borrar espacio**: solo si está vacío (sin periodos, tarjetas, plantillas ni transferencias que lo apunten). El principal no se puede borrar; se puede cambiar cuál es el principal (siempre exactamente uno).
- **Datos existentes**: la base real está vacía; los scripts de tablas cambian directamente (sin script de migración de datos).
- **Paridad con el Excel**: en Salario quincenal con saldo inicial ₡0, el restante del primer periodo es igual al "disponible" actual; los números del Excel no cambian.

## 9. Pantallas

- **Selector de espacio** en el topbar (y en el sidebar móvil), recordado por dispositivo; todas las pantallas (Inicio, Quincenas→**Periodos**, Cuotas, Plantillas) trabajan sobre el espacio seleccionado.
- **Nueva pantalla Espacios** (menú): crear/editar/borrar (lógico) espacios: nombre, frecuencia, días (custom), saldo inicial, principal.
- **Periodo**: encabezado con fechas editables; tarjetas de resumen con Saldo anterior, Entró, Gastado, Restante (+ Falta por pagar); en el formulario de línea de gasto, campo opcional **"Enviar a espacio"**; líneas de transferencia recibidas marcadas "↘ desde Salario" (solo lectura).
- **Inicio**: resumen de todos los espacios (restante actual de cada uno) + lo de hoy del espacio seleccionado.
- Menú "Quincenas" pasa a llamarse **"Periodos"**.

## 10. Fuera de alcance (v1 de espacios)

Metas de ahorro, reportes/gráficos entre espacios, monedas distintas por espacio, transferencias programadas sin línea origen, login.

## 11. Calidad

- Casos de prueba: proyección de periodos por frecuencia (incluye fin de mes, febrero, año bisiesto, semanas que cruzan mes); reparto mensual (2/1/4/5 periodos, céntimos); saldo en cadena (positivo y negativo) y con arrastre sin doble conteo; transferencia pagar/despagar/editar/borrar; destino creado si falta; avance de cuotas por primer periodo del mes; los números del Excel siguen dando lo mismo en el espacio Salario quincenal.
- Scripts SQL idempotentes actualizados (sin SQL dinámico) + esquema de pruebas sqlite.
