/* ------------------------------------------------------- */
/*                   LOGIN DE LA APLICACION                */
/* ------------------------------------------------------- */
/*
 * Orden general de los scripts: database -> tables -> indexes -> logins.
 * Orden de esta carpeta:
 *   - 1. Application - Login.sql
 *
 * Este archivo requiere: todos los scripts anteriores y un usuario con permiso
 * CREATE USER y GRANT (p. ej. root).
 * Cada bloque valida si el objeto ya existe, por lo que el archivo se puede ejecutar mas
 * de una vez; al volver a ejecutarlo no cambia la contrasena de un usuario que ya existe.
 *
 * Crea un usuario exclusivo para la aplicacion, en lugar de usar root, con el minimo de
 * permisos: SELECT, INSERT, UPDATE y DELETE sobre money_tracker (sin permisos de DDL).
 *
 * Antes de ejecutarlo, cambie <CAMBIAR_PASSWORD_ALFANUMERICA> por una contrasena segura
 * que use solo letras y numeros (los caracteres especiales dan problemas en URLs y .env).
 * Despues, configure DB_USERNAME=money y DB_PASSWORD en el .env de la aplicacion.
 * Nunca guarde la contrasena real en este archivo ni lo suba con ella.
 */

/* --------------------------------------------------------------------- */
/* NOMBRE      : money                                                   */
/* ESQUEMA     : money_tracker                                           */
/* DESCRIPCION : Usuario de la aplicacion con permisos de datos.         */
/* --------------------------------------------------------------------- */
CREATE USER IF NOT EXISTS 'money'@'%' IDENTIFIED BY '<CAMBIAR_PASSWORD_ALFANUMERICA>';
GRANT SELECT, INSERT, UPDATE, DELETE ON money_tracker.* TO 'money'@'%';
FLUSH PRIVILEGES;
