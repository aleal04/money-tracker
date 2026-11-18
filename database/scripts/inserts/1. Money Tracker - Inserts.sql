USE money_tracker;

/* ------------------------------------------------------- */
/*                     DATOS INICIALES                     */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> inserts -> logins.
 * Orden de esta carpeta:
 *   - 1. Money Tracker - Inserts.sql
 *
 * Este archivo requiere: los scripts de database/, tables/ e indexes/.
 * Cada INSERT ... SELECT ... WHERE NOT EXISTS valida si el dato ya existe, por lo que el
 * archivo se puede ejecutar mas de una vez (sin SQL dinamico).
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : Space (Salario)                                         */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Espacio principal, quincenal y con saldo inicial 0. Se  */
/*               inserta solo si no hay ya un espacio principal vivo.    */
/* --------------------------------------------------------------------- */
INSERT INTO `Space` (Name, Frequency, CustomDays, InitialBalance, IsPrimary, Sort)
SELECT 'Salario', 'biweekly', NULL, 0, 1, 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `Space` WHERE IsPrimary = 1 AND IsDeleted = 0);
