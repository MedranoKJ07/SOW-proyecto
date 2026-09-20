-- Datos mínimos y seguros para pruebas locales.
-- Contraseñas de desarrollo documentadas en README_LOCAL.md.
USE optica;
SET NAMES utf8mb4;

INSERT INTO usuarios (nombre, usuario, email, `contraseña`, rol, mfa_email)
VALUES
  ('Administrador local', 'admin', 'admin@optica.local', '$2y$10$MJJHrrOE7oiKn9BvoifJsuR0rfGDhj97HcpQmJWslcW.vaabsnNRm', 'admin', 0),
  ('Doctor local', 'doctor', 'doctor@optica.local', '$2y$10$qHCiTsf/cKTRB8m05r0wxeN80u69CNZksNfTJTBJ/6kY.APS8c0fu', 'doctor', 0),
  ('Secretaría local', 'secretaria', 'secretaria@optica.local', '$2y$10$7.2LLdXgU8RFTHQSazpnA.pt12u0Hbe05YEe3ov4yHBOIQg5quKTa', 'secretaria', 0)
ON DUPLICATE KEY UPDATE usuario = VALUES(usuario);

INSERT INTO servicios (nombre, duracion_min, precio, activo)
VALUES
  ('Consulta general', 30, 0.00, 1),
  ('Examen de la vista', 40, 0.00, 1),
  ('Retiro/entrega de lentes', 15, 0.00, 1),
  ('Otro', 30, 0.00, 1)
ON DUPLICATE KEY UPDATE activo = VALUES(activo);

INSERT INTO productos (sku, nombre, tipo, unidad, precio_venta, stock_minimo, activo)
VALUES
  ('MAR-101-BLK', 'Armazón clásico negro', 'Marco', 'u', 850.00, 3, 1),
  ('LEN-CR39', 'Cristal CR-39 genérico', 'Lente', 'par', 300.00, 5, 1),
  ('ACC-PANO', 'Paño microfibra', 'Accesorio', 'u', 40.00, 10, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = VALUES(activo);

INSERT INTO proveedores (nombre, nombre_comercial, telefono, email, ciudad, estado)
SELECT 'Proveedor local de prueba', 'Proveedor local', '', 'proveedor@optica.local', 'Local', 1
WHERE NOT EXISTS (SELECT 1 FROM proveedores WHERE email = 'proveedor@optica.local');
