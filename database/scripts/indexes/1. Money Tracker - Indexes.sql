USE money_tracker;

/* ------------------------------------------------------- */
/*                         INDICES                         */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> logins.
 * Orden de esta carpeta:
 *   - 1. Money Tracker - Indexes.sql
 *
 * Este archivo requiere: los scripts de database/ y tables/.
 * Decision: las llaves unicas se crean aqui (no dentro de CREATE TABLE) y cada una valida
 * en information_schema.statistics si ya existe, con un SQL preparado; asi el archivo se
 * puede ejecutar mas de una vez. Nombres al estilo ssi: UQ_<Tabla>_<Columnas>.
 * No hay indices secundarios adicionales (IX_): MySQL ya crea uno por cada llave foranea
 * (FkPeriod, FkTemplate, FkCard, FkCarriedToLine) al ejecutar tables/.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : UQ_Card_ActiveName                                      */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Una tarjeta viva por nombre; las borradas               */
/*               tienen ActiveName NULL y se ignoran.                    */
/* --------------------------------------------------------------------- */
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `Card` ADD CONSTRAINT UQ_Card_ActiveName UNIQUE (ActiveName)',
    'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'Card' AND index_name = 'UQ_Card_ActiveName');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


/* --------------------------------------------------------------------- */
/* NOMBRE      : UQ_Period_Year_Month_Half                               */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Una quincena por (anio, mes, mitad).                    */
/* --------------------------------------------------------------------- */
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `Period` ADD CONSTRAINT UQ_Period_Year_Month_Half UNIQUE (Year, Month, Half)',
    'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'Period' AND index_name = 'UQ_Period_Year_Month_Half');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


/* --------------------------------------------------------------------- */
/* NOMBRE      : UQ_Line_FkPeriod_CuotasCardKey                          */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Una sola linea de cuotas viva por (periodo,             */
/*               tarjeta); las demas tienen CuotasCardKey NULL.          */
/* --------------------------------------------------------------------- */
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `Line` ADD CONSTRAINT UQ_Line_FkPeriod_CuotasCardKey UNIQUE (FkPeriod, CuotasCardKey)',
    'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'Line' AND index_name = 'UQ_Line_FkPeriod_CuotasCardKey');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
