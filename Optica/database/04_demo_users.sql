-- Crea o restablece los usuarios demo locales.
-- Ejecutar después de database/setup_local.sql.

CREATE DATABASE IF NOT EXISTS optica
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE optica;
SET NAMES utf8mb4;

INSERT INTO usuarios (nombre, usuario, email, `contraseña`, rol, mfa_email)
VALUES
  ('Administrador local', 'admin', 'admin@optica.local', '$2y$10$MJJHrrOE7oiKn9BvoifJsuR0rfGDhj97HcpQmJWslcW.vaabsnNRm', 'admin', 0),
  ('Doctor local', 'doctor', 'doctor@optica.local', '$2y$10$qHCiTsf/cKTRB8m05r0wxeN80u69CNZksNfTJTBJ/6kY.APS8c0fu', 'doctor', 0),
  ('Secretaría local', 'secretaria', 'secretaria@optica.local', '$2y$10$7.2LLdXgU8RFTHQSazpnA.pt12u0Hbe05YEe3ov4yHBOIQg5quKTa', 'secretaria', 0)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  email = VALUES(email),
  `contraseña` = VALUES(`contraseña`),
  rol = VALUES(rol),
  mfa_email = VALUES(mfa_email);
