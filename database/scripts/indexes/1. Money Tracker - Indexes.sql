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
 * puede ejecutar mas de una vez. Los nombres son los mismos que generaba Laravel.
 * No hay indices secundarios adicionales: MySQL ya crea uno por cada llave foranea
 * (period_id, template_id, card_id, carried_to_line_id) al ejecutar tables/.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : cards_name_unique                                       */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Una tarjeta por nombre.                                 */
/* --------------------------------------------------------------------- */
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE cards ADD UNIQUE INDEX cards_name_unique (name)',
    'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'cards' AND index_name = 'cards_name_unique');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


/* --------------------------------------------------------------------- */
/* NOMBRE      : periods_year_month_half_unique                          */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Una quincena por (anio, mes, mitad).                    */
/* --------------------------------------------------------------------- */
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE periods ADD UNIQUE INDEX periods_year_month_half_unique (year, month, half)',
    'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'periods' AND index_name = 'periods_year_month_half_unique');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


/* --------------------------------------------------------------------- */
/* NOMBRE      : lines_period_id_cuotas_card_id_unique                   */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Una sola linea de cuotas por (periodo, tarjeta); las    */
/*               demas lineas tienen cuotas_card_id NULL y se ignoran.   */
/* --------------------------------------------------------------------- */
SET @sql = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `lines` ADD UNIQUE INDEX lines_period_id_cuotas_card_id_unique (period_id, cuotas_card_id)',
    'DO 0')
    FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'lines' AND index_name = 'lines_period_id_cuotas_card_id_unique');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
