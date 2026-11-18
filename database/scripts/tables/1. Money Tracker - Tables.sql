USE money_tracker;

/* ------------------------------------------------------- */
/*                          TABLAS                         */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> logins.
 * Orden de esta carpeta:
 *   - 1. Money Tracker - Tables.sql
 *
 * Este archivo requiere: la base money_tracker (script de database/).
 * Cada bloque valida si el objeto ya existe, por lo que el archivo se puede ejecutar mas
 * de una vez. Dentro del archivo las tablas van en orden de llaves foraneas:
 * cards, templates, periods, lines, installments.
 *
 * Las llaves unicas NO se crean aqui: viven en indexes/ (con el mismo nombre que
 * generaba Laravel). Los montos son decimal(14,2); en PHP se manejan como centavos enteros.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : cards                                                   */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Tarjetas de credito sobre las que se registran cuotas.  */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS cards (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255)    NOT NULL,
    created_at TIMESTAMP       NULL,
    updated_at TIMESTAMP       NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : templates                                               */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Plantillas de lineas recurrentes que se expanden al     */
/*               crear un periodo (frecuencia y quincena opcional).      */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS templates (
    id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255)     NOT NULL,
    category   VARCHAR(255)     NOT NULL,
    estimated  DECIMAL(14,2)    NOT NULL,
    frequency  VARCHAR(255)     NOT NULL,
    half       TINYINT UNSIGNED NULL,
    active     TINYINT(1)       NOT NULL DEFAULT 1,
    created_at TIMESTAMP        NULL,
    updated_at TIMESTAMP        NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : periods                                                 */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Quincenas (anio, mes, mitad 1 o 2); closed indica que   */
/*               el periodo ya se cerro.                                 */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS periods (
    id         BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    year       SMALLINT UNSIGNED NOT NULL,
    month      TINYINT UNSIGNED  NOT NULL,
    half       TINYINT UNSIGNED  NOT NULL,
    closed     TINYINT(1)        NOT NULL DEFAULT 0,
    created_at TIMESTAMP         NULL,
    updated_at TIMESTAMP         NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : lines                                                   */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Lineas de cada periodo. cuotas_card_id es una columna   */
/*               generada VIRTUAL: vale card_id solo cuando source es    */
/*               'installments' y NULL en los demas casos; MySQL no      */
/*               tiene indices parciales y UNIQUE ignora los NULL, asi   */
/*               hay una sola linea de cuotas por (periodo, tarjeta).    */
/*               Requiere: cards, templates y periods.                   */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `lines` (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id          BIGINT UNSIGNED NOT NULL,
    category           VARCHAR(255)    NOT NULL,
    name               VARCHAR(255)    NOT NULL,
    estimated          DECIMAL(14,2)   NOT NULL DEFAULT 0,
    actual             DECIMAL(14,2)   NULL,
    paid               TINYINT(1)      NOT NULL DEFAULT 0,
    paid_at            TIMESTAMP       NULL,
    source             VARCHAR(255)    NOT NULL DEFAULT 'manual',
    template_id        BIGINT UNSIGNED NULL,
    card_id            BIGINT UNSIGNED NULL,
    carried_to_line_id BIGINT UNSIGNED NULL,
    sort               INT             NOT NULL DEFAULT 0,
    cuotas_card_id     BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN source = 'installments' THEN card_id ELSE NULL END) VIRTUAL,
    created_at         TIMESTAMP       NULL,
    updated_at         TIMESTAMP       NULL,
    PRIMARY KEY (id),
    CONSTRAINT lines_period_id_foreign FOREIGN KEY (period_id) REFERENCES periods (id) ON DELETE CASCADE,
    CONSTRAINT lines_template_id_foreign FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE SET NULL,
    CONSTRAINT lines_card_id_foreign FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE SET NULL,
    CONSTRAINT lines_carried_to_line_id_foreign FOREIGN KEY (carried_to_line_id) REFERENCES `lines` (id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : installments                                            */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Compras a cuotas de una tarjeta. No se puede borrar una */
/*               tarjeta que tenga cuotas (ON DELETE RESTRICT).          */
/*               Requiere: cards.                                        */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS installments (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    card_id             BIGINT UNSIGNED   NOT NULL,
    merchant            VARCHAR(255)      NOT NULL,
    item                VARCHAR(255)      NOT NULL,
    total_installments  SMALLINT UNSIGNED NOT NULL,
    paid_installments   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    type                VARCHAR(255)      NOT NULL,
    initial_balance     DECIMAL(14,2)     NOT NULL,
    installment_amount  DECIMAL(14,2)     NULL,
    balance_override    DECIMAL(14,2)     NULL,
    cancel_soon         TINYINT(1)        NOT NULL DEFAULT 0,
    last_advanced_month VARCHAR(7)        NOT NULL,
    created_at          TIMESTAMP         NULL,
    updated_at          TIMESTAMP         NULL,
    PRIMARY KEY (id),
    CONSTRAINT installments_card_id_foreign FOREIGN KEY (card_id) REFERENCES cards (id) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
