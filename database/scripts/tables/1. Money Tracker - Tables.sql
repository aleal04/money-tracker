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
 * Card, Template, Period, Line, Installment.
 *
 * Convenciones (las del proyecto ssi): tablas y columnas en PascalCase, llave primaria
 * Id<Tabla>, llaves foraneas Fk<Referencia>, restricciones PK_/FK_/UQ_/IX_. Use siempre el
 * nombre exacto de la tabla: en Linux MySQL distingue mayusculas (lower_case_table_names=0)
 * y en macOS/Windows no.
 * Cada tabla lleva las columnas de auditoria de ssi; el borrado es logico (IsDeleted = 1 y
 * DeletedAt). CreatedBy/UpdatedBy/DeletedBy quedan NULL mientras no haya login.
 * Las llaves unicas NO se crean aqui: viven en indexes/. Los montos son DECIMAL(14,2); en
 * PHP se manejan como centavos enteros.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : Card                                                    */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Tarjetas de credito sobre las que se registran cuotas.  */
/*               ActiveName es una columna generada VIRTUAL: vale Name   */
/*               solo si la tarjeta no esta borrada, para que el nombre  */
/*               sea unico entre las tarjetas vivas (UQ_Card_ActiveName) */
/*               y se pueda reutilizar el de una tarjeta borrada.        */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Card` (
    IdCard     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    Name       VARCHAR(255)    NOT NULL,
    IsDeleted  TINYINT(1)      NOT NULL DEFAULT 0,
    CreatedBy  VARCHAR(50)     NULL,
    UpdatedBy  VARCHAR(50)     NULL,
    DeletedBy  VARCHAR(50)     NULL,
    CreatedAt  DATETIME        NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt  DATETIME        NULL,
    DeletedAt  DATETIME        NULL,
    ActiveName VARCHAR(255)    GENERATED ALWAYS AS (CASE WHEN IsDeleted = 0 THEN Name ELSE NULL END) VIRTUAL,
    CONSTRAINT PK_Card_IdCard PRIMARY KEY (IdCard)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Template                                                */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Plantillas de lineas recurrentes que se expanden al     */
/*               crear un periodo (frecuencia y quincena opcional).      */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Template` (
    IdTemplate BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    Name       VARCHAR(255)     NOT NULL,
    Category   VARCHAR(255)     NOT NULL,
    Estimated  DECIMAL(14,2)    NOT NULL,
    Frequency  VARCHAR(255)     NOT NULL,
    Half       TINYINT UNSIGNED NULL,
    Active     TINYINT(1)       NOT NULL DEFAULT 1,
    IsDeleted  TINYINT(1)       NOT NULL DEFAULT 0,
    CreatedBy  VARCHAR(50)      NULL,
    UpdatedBy  VARCHAR(50)      NULL,
    DeletedBy  VARCHAR(50)      NULL,
    CreatedAt  DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt  DATETIME         NULL,
    DeletedAt  DATETIME         NULL,
    CONSTRAINT PK_Template_IdTemplate PRIMARY KEY (IdTemplate)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Period                                                  */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Quincenas (anio, mes, mitad 1 o 2); Closed indica que   */
/*               el periodo ya se cerro. Los periodos no se borran.      */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Period` (
    IdPeriod  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    Year      SMALLINT UNSIGNED NOT NULL,
    Month     TINYINT UNSIGNED  NOT NULL,
    Half      TINYINT UNSIGNED  NOT NULL,
    Closed    TINYINT(1)        NOT NULL DEFAULT 0,
    IsDeleted TINYINT(1)        NOT NULL DEFAULT 0,
    CreatedBy VARCHAR(50)       NULL,
    UpdatedBy VARCHAR(50)       NULL,
    DeletedBy VARCHAR(50)       NULL,
    CreatedAt DATETIME          NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME          NULL,
    DeletedAt DATETIME          NULL,
    CONSTRAINT PK_Period_IdPeriod PRIMARY KEY (IdPeriod)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Line                                                    */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Lineas de cada periodo. CuotasCardKey es una columna    */
/*               generada VIRTUAL: vale FkCard solo cuando Source es     */
/*               'installments' y la linea no esta borrada, y NULL en    */
/*               los demas casos; MySQL no tiene indices parciales y     */
/*               UNIQUE ignora los NULL, asi hay una sola linea de       */
/*               cuotas viva por (periodo, tarjeta). Es VIRTUAL (no      */
/*               STORED) porque FkCard tiene ON DELETE SET NULL.         */
/*               Requiere: Card, Template y Period.                      */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Line` (
    IdLine          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    FkPeriod        BIGINT UNSIGNED NOT NULL,
    Category        VARCHAR(255)    NOT NULL,
    Name            VARCHAR(255)    NOT NULL,
    Estimated       DECIMAL(14,2)   NOT NULL DEFAULT 0,
    Actual          DECIMAL(14,2)   NULL,
    Paid            TINYINT(1)      NOT NULL DEFAULT 0,
    PaidAt          DATETIME        NULL,
    Source          VARCHAR(255)    NOT NULL DEFAULT 'manual',
    FkTemplate      BIGINT UNSIGNED NULL,
    FkCard          BIGINT UNSIGNED NULL,
    FkCarriedToLine BIGINT UNSIGNED NULL,
    Sort            INT             NOT NULL DEFAULT 0,
    IsDeleted       TINYINT(1)      NOT NULL DEFAULT 0,
    CreatedBy       VARCHAR(50)     NULL,
    UpdatedBy       VARCHAR(50)     NULL,
    DeletedBy       VARCHAR(50)     NULL,
    CreatedAt       DATETIME        NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt       DATETIME        NULL,
    DeletedAt       DATETIME        NULL,
    CuotasCardKey   BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN Source = 'installments' AND IsDeleted = 0 THEN FkCard ELSE NULL END) VIRTUAL,
    CONSTRAINT PK_Line_IdLine PRIMARY KEY (IdLine),
    CONSTRAINT FK_Line_Period FOREIGN KEY (FkPeriod) REFERENCES `Period` (IdPeriod) ON DELETE CASCADE,
    CONSTRAINT FK_Line_Template FOREIGN KEY (FkTemplate) REFERENCES `Template` (IdTemplate) ON DELETE SET NULL,
    CONSTRAINT FK_Line_Card FOREIGN KEY (FkCard) REFERENCES `Card` (IdCard) ON DELETE SET NULL,
    CONSTRAINT FK_Line_CarriedToLine FOREIGN KEY (FkCarriedToLine) REFERENCES `Line` (IdLine) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Installment                                             */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Compras a cuotas de una tarjeta. No se puede borrar     */
/*               fisicamente una tarjeta que tenga cuotas                */
/*               (ON DELETE RESTRICT). Requiere: Card.                   */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Installment` (
    IdInstallment     BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    FkCard            BIGINT UNSIGNED   NOT NULL,
    Merchant          VARCHAR(255)      NOT NULL,
    Item              VARCHAR(255)      NOT NULL,
    TotalInstallments SMALLINT UNSIGNED NOT NULL,
    PaidInstallments  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    Type              VARCHAR(255)      NOT NULL,
    InitialBalance    DECIMAL(14,2)     NOT NULL,
    InstallmentAmount DECIMAL(14,2)     NULL,
    BalanceOverride   DECIMAL(14,2)     NULL,
    CancelSoon        TINYINT(1)        NOT NULL DEFAULT 0,
    LastAdvancedMonth VARCHAR(7)        NOT NULL,
    IsDeleted         TINYINT(1)        NOT NULL DEFAULT 0,
    CreatedBy         VARCHAR(50)       NULL,
    UpdatedBy         VARCHAR(50)       NULL,
    DeletedBy         VARCHAR(50)       NULL,
    CreatedAt         DATETIME          NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt         DATETIME          NULL,
    DeletedAt         DATETIME          NULL,
    CONSTRAINT PK_Installment_IdInstallment PRIMARY KEY (IdInstallment),
    CONSTRAINT FK_Installment_Card FOREIGN KEY (FkCard) REFERENCES `Card` (IdCard) ON DELETE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
