/* ------------------------------------------------------- */
/*                      BASE DE DATOS                      */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> logins.
 * Orden de esta carpeta:
 *   - 1. Money Tracker - Database.sql
 *
 * Este archivo requiere: un usuario con permiso CREATE sobre el servidor (p. ej. root).
 * Cada bloque valida si el objeto ya existe, por lo que el archivo se puede ejecutar mas
 * de una vez.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : money_tracker                                           */
/* ESQUEMA     : (servidor)                                              */
/* DESCRIPCION : Base de la aplicacion, en utf8mb4 para soportar tildes  */
/*               y emojis en los nombres.                                */
/* --------------------------------------------------------------------- */
CREATE DATABASE IF NOT EXISTS money_tracker
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
