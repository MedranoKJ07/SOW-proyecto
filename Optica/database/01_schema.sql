-- Esquema estable para la instalación local de Óptica SOW.
-- No contiene datos de producción ni comandos de inspección.

CREATE DATABASE IF NOT EXISTS optica
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE optica;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  usuario VARCHAR(100) NOT NULL,
  email VARCHAR(160) NOT NULL,
  `contraseña` VARCHAR(255) NOT NULL,
  rol ENUM('admin','doctor','secretaria') NOT NULL DEFAULT 'secretaria',
  reset_token VARCHAR(255) NULL,
  reset_expira DATETIME NULL,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  mfa_email TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME NULL,
  last_login_ip VARCHAR(45) NULL,
  last_login_ua VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_usuarios_usuario (usuario),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pacientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  telefono VARCHAR(30) NOT NULL,
  edad TINYINT UNSIGNED NULL,
  cedula VARCHAR(30) NULL,
  cedula_norm VARCHAR(30) NULL,
  correo VARCHAR(160) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pacientes_cedula_norm (cedula_norm),
  KEY idx_pacientes_telefono_nombre (telefono, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  duracion_min SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_servicios_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS citas_medicas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  motivo VARCHAR(255) NOT NULL DEFAULT '',
  tipo VARCHAR(30) NOT NULL DEFAULT 'otro',
  estado ENUM('pendiente','en_atencion','atendida','cancelada','realizada','ausente') NOT NULL DEFAULT 'pendiente',
  origen VARCHAR(30) NOT NULL DEFAULT 'interno',
  token VARCHAR(64) NULL,
  checkin_en DATETIME NULL,
  inicio_atencion DATETIME NULL,
  fin_atencion DATETIME NULL,
  diagnostico TEXT NULL,
  indicaciones TEXT NULL,
  notas TEXT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_citas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_citas_doctor FOREIGN KEY (doctor_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  UNIQUE KEY uq_citas_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historial_clinico (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNSIGNED NOT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  esfera_od VARCHAR(40) NULL,
  cilindro_od VARCHAR(40) NULL,
  eje_od VARCHAR(40) NULL,
  adicion_od VARCHAR(40) NULL,
  avs_lentes_od VARCHAR(40) NULL,
  avc_lentes_od VARCHAR(40) NULL,
  dip_od VARCHAR(40) NULL,
  altura_od VARCHAR(40) NULL,
  esfera_oi VARCHAR(40) NULL,
  cilindro_oi VARCHAR(40) NULL,
  eje_oi VARCHAR(40) NULL,
  adicion_oi VARCHAR(40) NULL,
  avs_lentes_oi VARCHAR(40) NULL,
  avc_lentes_oi VARCHAR(40) NULL,
  dip_oi VARCHAR(40) NULL,
  altura_oi VARCHAR(40) NULL,
  tipo_lente VARCHAR(100) NULL,
  diagnostico TEXT NULL,
  observaciones TEXT NULL,
  medidas_anteriores TEXT NULL,
  CONSTRAINT fk_historial_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS facturas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  tipo_lente VARCHAR(100) NULL,
  esfera_od VARCHAR(40) NULL,
  cilindro_od VARCHAR(40) NULL,
  eje_od VARCHAR(40) NULL,
  esfera_oi VARCHAR(40) NULL,
  cilindro_oi VARCHAR(40) NULL,
  eje_oi VARCHAR(40) NULL,
  adicion VARCHAR(40) NULL,
  avs_lentes VARCHAR(40) NULL,
  avc_lentes VARCHAR(40) NULL,
  dip VARCHAR(40) NULL,
  altura VARCHAR(40) NULL,
  diagnostico TEXT NULL,
  observaciones TEXT NULL,
  medidas_anteriores TEXT NULL,
  total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  deuda DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  estado_pago ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_facturas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_factura (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id INT UNSIGNED NOT NULL,
  servicio_id INT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,3) NOT NULL DEFAULT 1.000,
  precio_unitario DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  subtotal DECIMAL(14,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
  CONSTRAINT fk_detalle_factura_factura FOREIGN KEY (factura_id) REFERENCES facturas(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_detalle_factura_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id INT UNSIGNED NOT NULL,
  fecha_pago DATE NOT NULL,
  monto DECIMAL(14,2) NOT NULL,
  metodo_pago VARCHAR(30) NOT NULL DEFAULT 'efectivo',
  referencia VARCHAR(100) NULL,
  observacion VARCHAR(255) NULL,
  CONSTRAINT fk_pagos_factura FOREIGN KEY (factura_id) REFERENCES facturas(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proveedores (
  id_proveedor INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  nombre_comercial VARCHAR(120) NULL,
  ruc VARCHAR(30) NULL,
  telefono VARCHAR(30) NULL,
  email VARCHAR(160) NULL,
  whatsapp VARCHAR(30) NULL,
  ciudad VARCHAR(80) NULL,
  departamento VARCHAR(80) NULL,
  direccion VARCHAR(255) NULL,
  contacto_nombre VARCHAR(120) NULL,
  contacto_cargo VARCHAR(80) NULL,
  contacto_telefono VARCHAR(30) NULL,
  contacto_email VARCHAR(160) NULL,
  rubro ENUM('Marcos','Lentes','Laboratorio','Insumos','Mixto') NOT NULL DEFAULT 'Mixto',
  dias_credito SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  limite_credito DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  estado TINYINT(1) NOT NULL DEFAULT 1,
  notas TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_proveedores_ruc (ruc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compras (
  id_compra INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_proveedor INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  num_documento VARCHAR(50) NULL,
  subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  descuento DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  impuesto DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_compras_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Convención única elegida por el código PHP: id_compra/producto/costo_unitario.
CREATE TABLE IF NOT EXISTS detalle_compra (
  id_detalle_compra INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_compra INT UNSIGNED NOT NULL,
  producto VARCHAR(200) NOT NULL,
  cantidad DECIMAL(12,3) NOT NULL,
  costo_unitario DECIMAL(14,4) NOT NULL,
  subtotal DECIMAL(14,2) GENERATED ALWAYS AS (cantidad * costo_unitario) STORED,
  CONSTRAINT fk_detalle_compra_compra FOREIGN KEY (id_compra) REFERENCES compras(id_compra)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
  id_producto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(40) NULL,
  nombre VARCHAR(150) NOT NULL,
  tipo ENUM('Marco','Lente','Accesorio','Insumo') NOT NULL DEFAULT 'Marco',
  unidad ENUM('u','par') NOT NULL DEFAULT 'u',
  precio_venta DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  costo_promedio DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  stock_minimo DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  stock_actual DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  notas TEXT NULL,
  UNIQUE KEY uq_productos_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inv_movimientos (
  id_mov INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo ENUM('COMPRA','VENTA','AJUSTE','DEV_COMPRA','DEV_VENTA') NOT NULL,
  referencia_id INT UNSIGNED NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad DECIMAL(12,3) NOT NULL,
  costo_unit DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  nota VARCHAR(180) NULL,
  CONSTRAINT fk_inv_mov_producto FOREIGN KEY (producto_id) REFERENCES productos(id_producto)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de compatibilidad para el módulo antiguo; el módulo vigente usa detalle_compra.
CREATE TABLE IF NOT EXISTS inventario (
  id_inventario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NULL,
  producto VARCHAR(120) NOT NULL,
  categoria VARCHAR(80) NULL,
  cantidad DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  costo DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_total DECIMAL(14,2) GENERATED ALWAYS AS (cantidad * costo) STORED,
  estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inventario_producto FOREIGN KEY (producto_id) REFERENCES productos(id_producto)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagos_proveedor (
  id_pago INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_compra INT UNSIGNED NOT NULL,
  fecha_pago DATE NOT NULL,
  monto DECIMAL(14,2) NOT NULL,
  metodo_pago ENUM('efectivo','transferencia','cheque','tarjeta') NOT NULL DEFAULT 'efectivo',
  referencia VARCHAR(80) NULL,
  observacion VARCHAR(255) NULL,
  CONSTRAINT fk_pagos_proveedor_compra FOREIGN KEY (id_compra) REFERENCES compras(id_compra)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gastos_operativos (
  id_gasto INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  descripcion VARCHAR(255) NOT NULL,
  monto DECIMAL(14,2) NOT NULL,
  fecha DATE NOT NULL,
  tipo VARCHAR(50) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimientos_caja (
  id_movimiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(30) NOT NULL,
  descripcion VARCHAR(255) NOT NULL,
  monto DECIMAL(14,2) NOT NULL,
  fecha DATE NOT NULL,
  id_gasto INT UNSIGNED NULL,
  id_compra INT UNSIGNED NULL,
  CONSTRAINT fk_mov_caja_gasto FOREIGN KEY (id_gasto) REFERENCES gastos_operativos(id_gasto)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_mov_caja_compra FOREIGN KEY (id_compra) REFERENCES compras(id_compra)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_audit (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  usuario VARCHAR(100) NOT NULL,
  exito TINYINT(1) NOT NULL,
  motivo VARCHAR(100) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_audit_user (user_id),
  CONSTRAINT fk_login_audit_user FOREIGN KEY (user_id) REFERENCES usuarios(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mfa_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code CHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  used TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mfa_user (user_id),
  UNIQUE KEY uq_mfa_user_code_used (user_id, code, used),
  CONSTRAINT fk_mfa_user FOREIGN KEY (user_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
