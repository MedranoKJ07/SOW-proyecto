# Mapa de datos de Óptica SOW

## Configuración

La conexión se crea en `conexion.php` mediante `conectarDB()`. Los valores se leen de `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` y `DB_NAME`; no deben escribirse en PHP ni en Git. La conexión usa `utf8mb4` y falla con un mensaje genérico mientras registra únicamente el código técnico en el log.

## Módulos y tablas

| Módulo | Tablas principales | Relaciones observadas |
| --- | --- | --- |
| Identidad y seguridad | `usuarios`, `login_audit`, `mfa_codes` | MFA y auditoría apuntan al usuario |
| Atención clínica | `pacientes`, `citas_medicas`, `historial_clinico` | Citas e historial apuntan al paciente; las citas pueden apuntar al doctor |
| Catálogo e inventario | `productos`, `inventario`, `inv_movimientos`, `servicios` | Movimientos y detalles apuntan a productos |
| Compras | `proveedores`, `compras`, `detalle_compra`, `pagos_proveedor` | Compras apuntan a proveedores; detalles a compras y productos; pagos a compras |
| Facturación | `facturas`, `detalle_factura`, `pagos` | Facturas apuntan a pacientes; detalles a servicios; pagos a facturas |
| Contabilidad | `gastos_operativos`, `movimientos_caja` | Movimientos registran ingresos/egresos y pueden referenciar compras |

## Índices relevantes

Los scripts SQL disponibles definen índices para búsquedas por nombre de paciente/proveedor/producto y para agenda por `fecha`, `estado` y `doctor_id`. Antes de añadir índices nuevos se debe comprobar el esquema desplegado, porque los SQL recuperados son auxiliares y no sustituyen una migración versionada.

## Regla de mantenimiento

Las nuevas consultas deben usar `prepare()` y parámetros enlazados. Si una modificación cambia columnas, claves o índices, debe incluir el SQL de migración y registrar la prueba ejecutada contra una base de datos de prueba.
