-- Vistas e índices derivados. Se puede ejecutar varias veces.
USE optica;
SET NAMES utf8mb4;

CREATE OR REPLACE VIEW vw_compra_saldos AS
SELECT
  c.id_compra,
  c.id_proveedor,
  c.fecha,
  c.num_documento,
  c.subtotal,
  c.descuento,
  c.impuesto,
  c.total,
  COALESCE(p.pagado, 0) AS pagado,
  c.total - COALESCE(p.pagado, 0) AS saldo,
  CASE
    WHEN COALESCE(p.pagado, 0) <= 0 THEN 'pendiente'
    WHEN COALESCE(p.pagado, 0) < c.total THEN 'parcial'
    ELSE 'pagado'
  END AS estado_inferido
FROM compras c
LEFT JOIN (
  SELECT id_compra, SUM(monto) AS pagado
  FROM pagos_proveedor
  GROUP BY id_compra
) p ON p.id_compra = c.id_compra;

CREATE OR REPLACE VIEW vw_proveedor_deuda AS
SELECT
  pr.id_proveedor,
  pr.nombre,
  pr.nombre_comercial,
  COALESCE(SUM(c.total), 0) AS comprado,
  COALESCE(SUM(COALESCE(s.pagado, 0)), 0) AS pagado,
  COALESCE(SUM(c.total - COALESCE(s.pagado, 0)), 0) AS saldo
FROM proveedores pr
LEFT JOIN compras c ON c.id_proveedor = pr.id_proveedor
LEFT JOIN (
  SELECT id_compra, SUM(monto) AS pagado
  FROM pagos_proveedor
  GROUP BY id_compra
) s ON s.id_compra = c.id_compra
GROUP BY pr.id_proveedor, pr.nombre, pr.nombre_comercial;

CREATE OR REPLACE VIEW vw_stock_actual AS
SELECT
  m.producto_id,
  SUM(CASE
    WHEN m.tipo IN ('COMPRA','AJUSTE','DEV_VENTA') THEN m.cantidad
    WHEN m.tipo IN ('VENTA','DEV_COMPRA') THEN -m.cantidad
    ELSE 0
  END) AS stock_actual
FROM inv_movimientos m
GROUP BY m.producto_id;

DROP PROCEDURE IF EXISTS _optica_add_index;
DELIMITER $$
CREATE PROCEDURE _optica_add_index(IN p_table VARCHAR(64), IN p_name VARCHAR(64), IN p_sql TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_name
  ) THEN
    SET @optica_index_sql = p_sql;
    PREPARE optica_index_stmt FROM @optica_index_sql;
    EXECUTE optica_index_stmt;
    DEALLOCATE PREPARE optica_index_stmt;
  END IF;
END$$
DELIMITER ;

CALL _optica_add_index('pacientes', 'idx_pacientes_nombre', 'CREATE INDEX idx_pacientes_nombre ON pacientes(nombre)');
CALL _optica_add_index('citas_medicas', 'idx_citas_fecha_estado', 'CREATE INDEX idx_citas_fecha_estado ON citas_medicas(fecha, estado)');
CALL _optica_add_index('citas_medicas', 'idx_citas_doctor', 'CREATE INDEX idx_citas_doctor ON citas_medicas(doctor_id, fecha)');
CALL _optica_add_index('citas_medicas', 'idx_citas_paciente', 'CREATE INDEX idx_citas_paciente ON citas_medicas(paciente_id, fecha)');
CALL _optica_add_index('historial_clinico', 'idx_historial_paciente_fecha', 'CREATE INDEX idx_historial_paciente_fecha ON historial_clinico(paciente_id, fecha)');
CALL _optica_add_index('facturas', 'idx_facturas_paciente_fecha', 'CREATE INDEX idx_facturas_paciente_fecha ON facturas(paciente_id, fecha)');
CALL _optica_add_index('pagos', 'idx_pagos_factura_fecha', 'CREATE INDEX idx_pagos_factura_fecha ON pagos(factura_id, fecha_pago)');
CALL _optica_add_index('proveedores', 'idx_proveedores_nombre', 'CREATE INDEX idx_proveedores_nombre ON proveedores(nombre)');
CALL _optica_add_index('proveedores', 'idx_proveedores_rubro', 'CREATE INDEX idx_proveedores_rubro ON proveedores(rubro)');
CALL _optica_add_index('compras', 'idx_compras_proveedor_fecha', 'CREATE INDEX idx_compras_proveedor_fecha ON compras(id_proveedor, fecha)');
CALL _optica_add_index('detalle_compra', 'idx_detalle_compra_producto', 'CREATE INDEX idx_detalle_compra_producto ON detalle_compra(producto)');
CALL _optica_add_index('pagos_proveedor', 'idx_pagos_proveedor_fecha', 'CREATE INDEX idx_pagos_proveedor_fecha ON pagos_proveedor(fecha_pago)');
CALL _optica_add_index('productos', 'idx_productos_nombre', 'CREATE INDEX idx_productos_nombre ON productos(nombre)');
CALL _optica_add_index('productos', 'idx_productos_tipo', 'CREATE INDEX idx_productos_tipo ON productos(tipo)');
CALL _optica_add_index('inv_movimientos', 'idx_inv_mov_producto_fecha', 'CREATE INDEX idx_inv_mov_producto_fecha ON inv_movimientos(producto_id, fecha)');
CALL _optica_add_index('gastos_operativos', 'idx_gastos_fecha', 'CREATE INDEX idx_gastos_fecha ON gastos_operativos(fecha)');
CALL _optica_add_index('movimientos_caja', 'idx_movimientos_caja_fecha', 'CREATE INDEX idx_movimientos_caja_fecha ON movimientos_caja(fecha)');

DROP PROCEDURE _optica_add_index;
