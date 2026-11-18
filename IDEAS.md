# Money Tracker — Ideas

App personal para reemplazar el Excel de finanzas. Prioridad: **funcionalidad** sobre seguridad avanzada (es de uso personal).
Moneda principal: CRC (₡).

---

## 1. Cómo funciona hoy (Excel)

### Hoja de quincena

- Periodo: **quincena** (ej. "I Quincena Octubre").
- Resumen: **Ingreso total**, **Egreso total**, **Disponible = Ingreso − Egreso**.
- 4 bloques, cada línea con `Nombre | Aproximado | Real | Pagado (SI/NO)`:
  - **Servicios**: Kolbi, Claro, CONAPE, Servicios digitales…
  - **Gastos**: Walmart, Pricesmart, recibo agua, gasolina… (variables)
  - **Ahorros**: Perros (cuenta BAC)…
  - **Deudas fijas**: préstamos y pagos de tarjeta (Banco Popular, TC Crédito BAC…)

### Hoja de cuotas de tarjeta

`Comercio | Artículo | Total cuotas | Cuotas actuales | Forma pago | Saldo inicial | Monto cuota | Saldo faltante`

- **Tasa 0**: `cuota = saldo inicial / total cuotas`, `faltante = saldo inicial − cuotas pagadas × cuota`.
- **Minicuotas** (con interés): la cuota viene del banco; `faltante = cuota × cuotas restantes` (puede ser mayor que el saldo inicial por los intereses).
- Colores (solo referencia visual):
  - **Azul**: activa, normal.
  - **Amarillo**: pronto a finalizar o que se quiere **cancelar anticipadamente** (ej. Price Smart Sila 0/12 está en amarillo aunque no ha empezado → decisión manual).
  - **Verde**: terminada.
- En la app: estado **automático** (activa / por terminar si quedan ≤ N cuotas / terminada) + una marca **manual "cancelar pronto"**.

### Cómo se conecta con la quincena

- La **suma de "Monto cuota"** de las compras activas = una línea **TC Crédito BAC** en **Deudas fijas**.
- La otra línea TC es **consumo extra arrastrado** de quincenas pasadas (gastos con tarjeta que no eran cuotas y se pagan después).
- En la app:
  - Línea de cuotas **calculada automáticamente** desde las compras activas.
  - **Pago mensual dividido entre 2 quincenas**: si el mes suma ₡100.000 en cuotas, cada quincena lleva ₡50.000. (Aplica igual a otros pagos mensuales.)
  - **Arrastre**: gastos con tarjeta de una quincena quedan como pendiente y aparecen como deuda en la(s) siguiente(s), hasta pagarse.

#### Inconsistencias encontradas en el Excel actual

- ExtremeTech (PC Gaming): 14 cuotas restantes × 25.465 = 356.510, pero la hoja dice 294.382.
- La vete (Maya): 15 × 8.350,48 = 125.257, pero la hoja dice 101.420.
  → La app debe calcular estos valores sola (o guardar el saldo real del estado de cuenta si difiere).

---

## 2. Funcionalidades — MVP (replicar el Excel, mejor)

- [ ] **Quincenas**: crear quincena nueva copiando las líneas recurrentes de la anterior (plantilla).
- [ ] **Líneas** por categoría (Servicios, Gastos, Ahorros, Deudas fijas) con aproximado, real y pagado.
- [ ] **Resumen**: ingreso, egreso, disponible, y **"falta por pagar"** (suma de lo no pagado).
- [ ] **Diferencia aproximado vs real** resaltada.
- [ ] **Compras en cuotas** (tasa 0 / minicuotas): cuota, cuotas pagadas, saldo faltante calculados.
- [ ] Marcar cuota como pagada → avanza "cuotas actuales" automáticamente.
- [ ] Compra terminada pasa a historial (el "verde" de hoy).

## 3. Siguientes ideas

- [ ] **Pago de tarjeta = suma de cuotas activas de esa tarjeta** (+ consumo normal) → se llena solo en la quincena.
- [ ] **Proyección**: cuánto vas a pagar de tarjeta en los próximos meses según las cuotas que terminan (ej. "en 2 meses te liberas ₡8.485 de Apple Teclado + Cámaras + Wallet").
- [ ] **Simulador "cancelar anticipado"**: si pago hoy el saldo faltante de X, cuánto libero por quincena y cuánto ahorro en intereses (minicuotas). Ordenar sugerencias por mayor alivio por colón pagado.
- [ ] **Deudas/préstamos con saldo**: Banco Popular, CONAPE → saldo restante y fecha estimada de fin.
- [ ] **Ahorros con meta**: cuánto llevas vs objetivo.
- [ ] **Gastos variables acumulativos**: registrar cada compra (Walmart, gasolina) y que sume al rubro.
- [ ] **Fechas de vencimiento + recordatorios**.
- [ ] **Historial y gráficos**: por categoría, por quincena, por mes.
- [ ] **Importar Excel** histórico.
- [ ] Soporte **CRC / USD**.
- [ ] Usable desde el **celular**.

## 4. Seguridad (simple)

- Login simple o app solo local. Nada más por ahora.

## 5. Preguntas abiertas

- ¿Qué es lo que más molesta hoy del Excel?
