-- ==================================================
-- Usuario de aplicación para el proyecto de reservas
-- Ejecutar solo en local tras importar reservas.sql
-- ==================================================

USE reservas;

CREATE USER IF NOT EXISTS 'reservas_app' @'localhost' IDENTIFIED BY 'contrasena';

GRANT SELECT, INSERT, UPDATE ON reservas.* TO 'reservas_app' @'localhost';

FLUSH PRIVILEGES;