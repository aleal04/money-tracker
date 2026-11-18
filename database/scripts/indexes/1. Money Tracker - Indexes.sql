USE money_tracker;

/* ------------------------------------------------------- */
/*                         INDICES                         */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> inserts -> logins.
 * Orden de esta carpeta:
 *   - 1. Money Tracker - Indexes.sql
 *
 * Este archivo requiere: los scripts de database/ y tables/.
 *
 * Por ahora no hay indices adicionales:
 *   - Las llaves unicas (UQ_Space_ActiveName, UQ_Space_ActivePrimary, UQ_Card_ActiveName,
 *     UQ_Period_FkSpace_ActiveStart, UQ_Line_FkPeriod_CuotasCardKey) se crean dentro de
 *     cada CREATE TABLE en tables/,
 *     sin SQL dinamico, para que los scripts corran en cualquier cliente.
 *   - MySQL ya crea un indice por cada llave foranea (FkSpace, FkTargetSpace, FkPeriod,
 *     FkTemplate, FkCard, FkCarriedToLine, FkTransferFromLine).
 *
 * Si se agrega un indice secundario (IX_<Tabla>_<Columnas>) a una base que ya existe,
 * escribirlo aqui como ALTER TABLE ... ADD INDEX y correrlo una sola vez: MySQL 8 no tiene
 * CREATE INDEX IF NOT EXISTS.
 */
