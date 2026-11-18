USE money_tracker;

/* ------------------------------------------------------- */
/*                          TABLAS                         */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> inserts -> logins.
 * Orden de esta carpeta:
 *   - 1. Money Tracker - Tables.sql
 *
 * Este archivo requiere: la base money_tracker (script de database/).
 * Cada bloque valida si el objeto ya existe, por lo que el archivo se puede ejecutar mas
 * de una vez. Dentro del archivo las tablas van en orden de llaves foraneas:
 * Space, Card, Template, Period, Line, Installment.
 * Las llaves unicas (UQ_) van dentro de cada CREATE TABLE IF NOT EXISTS, sin SQL dinamico
 * (SET @/PREPARE/EXECUTE), para que el archivo corra igual en la terminal y en clientes
 * graficos (extensiones de VS Code, DBeaver, Workbench).
 *
 * Convenciones (las del proyecto ssi): tablas y columnas en PascalCase, llave primaria
 * Id<Tabla>, llaves foraneas Fk<Referencia>, restricciones PK_/FK_/UQ_/IX_. Use siempre el
 * nombre exacto de la tabla: en Linux MySQL distingue mayusculas (lower_case_table_names=0)
 * y en macOS/Windows no.
 * Cada tabla lleva las columnas de auditoria de ssi; el borrado es logico (IsDeleted = 1 y
 * DeletedAt). CreatedBy/UpdatedBy/DeletedBy quedan NULL mientras no haya login.
 * Las llaves unicas van dentro de cada CREATE TABLE (ver arriba). Los montos son DECIMAL(14,2); en
 * PHP se manejan como centavos enteros.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : Space                                                   */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Espacios: bolsas de dinero con su propia frecuencia de  */
/*               periodos (weekly, biweekly, monthly, custom con         */
/*               CustomDays) y saldo inicial. ActiveName y ActivePrimary */
/*               son columnas generadas VIRTUAL para que el nombre sea   */
/*               unico entre los espacios vivos y haya un solo espacio   */
/*               principal vivo (UQ ignora los NULL).                    */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Space` (
    IdSpace        BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    Name           VARCHAR(255)      NOT NULL,
    Frequency      VARCHAR(255)      NOT NULL,
    CustomDays     SMALLINT UNSIGNED NULL,
    InitialBalance DECIMAL(14,2)     NOT NULL DEFAULT 0,
    IsPrimary      TINYINT(1)        NOT NULL DEFAULT 0,
    Sort           INT               NOT NULL DEFAULT 0,
    IsDeleted      TINYINT(1)        NOT NULL DEFAULT 0,
    CreatedBy      VARCHAR(50)       NULL,
    UpdatedBy      VARCHAR(50)       NULL,
    DeletedBy      VARCHAR(50)       NULL,
    CreatedAt      DATETIME          NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt      DATETIME          NULL,
    DeletedAt      DATETIME          NULL,
    ActiveName     VARCHAR(255)      GENERATED ALWAYS AS (CASE WHEN IsDeleted = 0 THEN Name ELSE NULL END) VIRTUAL,
    ActivePrimary  TINYINT(1)        GENERATED ALWAYS AS (CASE WHEN IsDeleted = 0 AND IsPrimary = 1 THEN 1 ELSE NULL END) VIRTUAL,
    CONSTRAINT PK_Space_IdSpace PRIMARY KEY (IdSpace),
    CONSTRAINT UQ_Space_ActiveName UNIQUE (ActiveName),
    CONSTRAINT UQ_Space_ActivePrimary UNIQUE (ActivePrimary)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Card                                                    */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Tarjetas de credito sobre las que se registran cuotas.  */
/*               ActiveName es una columna generada VIRTUAL: vale Name   */
/*               solo si la tarjeta no esta borrada, para que el nombre  */
/*               sea unico entre las tarjetas vivas (UQ_Card_ActiveName) */
/*               y se pueda reutilizar el de una tarjeta borrada. Su     */
/*               linea de cuotas va en los periodos de su espacio.       */
/*               Requiere: Space.                                        */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Card` (
    IdCard     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    FkSpace    BIGINT UNSIGNED NOT NULL,
    Name       VARCHAR(255)    NOT NULL,
    IsDeleted  TINYINT(1)      NOT NULL DEFAULT 0,
    CreatedBy  VARCHAR(50)     NULL,
    UpdatedBy  VARCHAR(50)     NULL,
    DeletedBy  VARCHAR(50)     NULL,
    CreatedAt  DATETIME        NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt  DATETIME        NULL,
    DeletedAt  DATETIME        NULL,
    ActiveName VARCHAR(255)    GENERATED ALWAYS AS (CASE WHEN IsDeleted = 0 THEN Name ELSE NULL END) VIRTUAL,
    CONSTRAINT PK_Card_IdCard PRIMARY KEY (IdCard),
    CONSTRAINT UQ_Card_ActiveName UNIQUE (ActiveName),
    CONSTRAINT FK_Card_Space FOREIGN KEY (FkSpace) REFERENCES `Space` (IdSpace)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Template                                                */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Plantillas de lineas recurrentes que se expanden al     */
/*               crear un periodo de su espacio. Frequency:              */
/*               cada_periodo, mensual_repartido o mensual_dia (con      */
/*               DayOfMonth 1-31). FkTargetSpace: plantilla de           */
/*               transferencia hacia otro espacio. Requiere: Space.      */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Template` (
    IdTemplate    BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    FkSpace       BIGINT UNSIGNED  NOT NULL,
    FkTargetSpace BIGINT UNSIGNED  NULL,
    Name          VARCHAR(255)     NOT NULL,
    Category      VARCHAR(255)     NOT NULL,
    Estimated     DECIMAL(14,2)    NOT NULL,
    Frequency     VARCHAR(255)     NOT NULL,
    DayOfMonth    TINYINT UNSIGNED NULL,
    Active        TINYINT(1)       NOT NULL DEFAULT 1,
    IsDeleted     TINYINT(1)       NOT NULL DEFAULT 0,
    CreatedBy     VARCHAR(50)      NULL,
    UpdatedBy     VARCHAR(50)      NULL,
    DeletedBy     VARCHAR(50)      NULL,
    CreatedAt     DATETIME         NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt     DATETIME         NULL,
    DeletedAt     DATETIME         NULL,
    CONSTRAINT PK_Template_IdTemplate PRIMARY KEY (IdTemplate),
    CONSTRAINT FK_Template_Space FOREIGN KEY (FkSpace) REFERENCES `Space` (IdSpace),
    CONSTRAINT FK_Template_TargetSpace FOREIGN KEY (FkTargetSpace) REFERENCES `Space` (IdSpace)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;


/* --------------------------------------------------------------------- */
/* NOMBRE      : Period                                                  */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Periodos de un espacio (StartDate a EndDate, ambos     */
/*               incluidos); Closed indica que el periodo ya se cerro.   */
/*               ActiveStart es una columna generada VIRTUAL: vale       */
/*               StartDate solo si el periodo no esta borrado, para que  */
/*               el inicio sea unico entre los periodos vivos del        */
/*               espacio (UQ_Period_FkSpace_ActiveStart).                */
/*               Requiere: Space.                                        */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Period` (
    IdPeriod    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    FkSpace     BIGINT UNSIGNED NOT NULL,
    StartDate   DATE            NOT NULL,
    EndDate     DATE            NOT NULL,
    Closed      TINYINT(1)      NOT NULL DEFAULT 0,
    IsDeleted   TINYINT(1)      NOT NULL DEFAULT 0,
    CreatedBy   VARCHAR(50)     NULL,
    UpdatedBy   VARCHAR(50)     NULL,
    DeletedBy   VARCHAR(50)     NULL,
    CreatedAt   DATETIME        NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt   DATETIME        NULL,
    DeletedAt   DATETIME        NULL,
    ActiveStart DATE            GENERATED ALWAYS AS (CASE WHEN IsDeleted = 0 THEN StartDate ELSE NULL END) VIRTUAL,
    CONSTRAINT PK_Period_IdPeriod PRIMARY KEY (IdPeriod),
    CONSTRAINT UQ_Period_FkSpace_ActiveStart UNIQUE (FkSpace, ActiveStart),
    CONSTRAINT FK_Period_Space FOREIGN KEY (FkSpace) REFERENCES `Space` (IdSpace)
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
/*               FkTargetSpace: gasto que se transfiere a otro espacio;  */
/*               FkTransferFromLine: en el ingreso creado en el espacio  */
/*               destino, la linea origen de la transferencia.          */
/*               Requiere: Space, Card, Template y Period.               */
/* --------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS `Line` (
    IdLine             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    FkPeriod           BIGINT UNSIGNED NOT NULL,
    Category           VARCHAR(255)    NOT NULL,
    Name               VARCHAR(255)    NOT NULL,
    Estimated          DECIMAL(14,2)   NOT NULL DEFAULT 0,
    Actual             DECIMAL(14,2)   NULL,
    Paid               TINYINT(1)      NOT NULL DEFAULT 0,
    PaidAt             DATETIME        NULL,
    Source             VARCHAR(255)    NOT NULL DEFAULT 'manual',
    FkTemplate         BIGINT UNSIGNED NULL,
    FkCard             BIGINT UNSIGNED NULL,
    FkCarriedToLine    BIGINT UNSIGNED NULL,
    FkTargetSpace      BIGINT UNSIGNED NULL,
    FkTransferFromLine BIGINT UNSIGNED NULL,
    Sort               INT             NOT NULL DEFAULT 0,
    IsDeleted          TINYINT(1)      NOT NULL DEFAULT 0,
    CreatedBy          VARCHAR(50)     NULL,
    UpdatedBy          VARCHAR(50)     NULL,
    DeletedBy          VARCHAR(50)     NULL,
    CreatedAt          DATETIME        NULL DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt          DATETIME        NULL,
    DeletedAt          DATETIME        NULL,
    CuotasCardKey      BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN Source = 'installments' AND IsDeleted = 0 THEN FkCard ELSE NULL END) VIRTUAL,
    CONSTRAINT PK_Line_IdLine PRIMARY KEY (IdLine),
    CONSTRAINT UQ_Line_FkPeriod_CuotasCardKey UNIQUE (FkPeriod, CuotasCardKey),
    CONSTRAINT FK_Line_Period FOREIGN KEY (FkPeriod) REFERENCES `Period` (IdPeriod) ON DELETE CASCADE,
    CONSTRAINT FK_Line_Template FOREIGN KEY (FkTemplate) REFERENCES `Template` (IdTemplate) ON DELETE SET NULL,
    CONSTRAINT FK_Line_Card FOREIGN KEY (FkCard) REFERENCES `Card` (IdCard) ON DELETE SET NULL,
    CONSTRAINT FK_Line_CarriedToLine FOREIGN KEY (FkCarriedToLine) REFERENCES `Line` (IdLine) ON DELETE SET NULL,
    CONSTRAINT FK_Line_TargetSpace FOREIGN KEY (FkTargetSpace) REFERENCES `Space` (IdSpace),
    CONSTRAINT FK_Line_TransferFromLine FOREIGN KEY (FkTransferFromLine) REFERENCES `Line` (IdLine) ON DELETE SET NULL
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
