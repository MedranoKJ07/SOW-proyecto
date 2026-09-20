# Evidencia de trabajo — Kerlint

Rama de trabajo: `dev/kerlint-database`

## Commit: `779c454`

- Objetivo: centralizar la conexión MySQL y eliminar credenciales codificadas.
- Autor: Kerlint `<kmurillojosue75@gmail.com>`
- Fecha: 2026-08-20
- Mensaje: `refactor(database): centralizar conexión mysql`
- Archivos: `conexion.php`
- Cambio: 47 líneas agregadas; 0 eliminadas.
- Problema encontrado: credenciales de hosting incrustadas, errores expuestos y ausencia de una conexión compartida `$conn`.
- Solución: variables de entorno obligatorias, validación de puerto, charset `utf8mb4`, error genérico y conexión compartida compatible.
- Prueba ejecutada: `php -l conexion.php`
- Resultado: PASS — No syntax errors detected.

## Commit: `58de19b`

- Objetivo: separar configuración de despliegue de la configuración versionada.
- Autor: Kerlint `<kmurillojosue75@gmail.com>`
- Fecha: 2026-08-20
- Mensaje: `security(config): separar credenciales de base de datos`
- Archivos: `.gitignore`, `config.php`
- Cambio: 18 líneas agregadas; 2 eliminadas.
- Problema encontrado: `config.php` estaba ignorado aunque ya no contenía secretos, y la URL base no admitía configuración explícita por entorno.
- Solución: permitir versionar el archivo saneado y priorizar `BASE_URL` del entorno con fallback seguro.
- Prueba ejecutada: `php -l config.php`
- Resultado: PASS — No syntax errors detected.

## Commit: `63a3bb7`

- Objetivo: documentar tablas, relaciones e índices para futuras consultas y migraciones.
- Autor: Kerlint `<kmurillojosue75@gmail.com>`
- Fecha: 2026-08-20
- Mensaje: `docs(database): documentar tablas utilizadas`
- Archivos: `docs/database-schema.md`
- Cambio: 24 líneas agregadas; 0 eliminadas.
- Problema encontrado: el esquema estaba disperso entre SQL auxiliares y consultas PHP sin un mapa de mantenimiento.
- Solución: mapa por módulo, relaciones observadas, índices y reglas para consultas parametrizadas.
- Prueba ejecutada: revisión de `rg` sobre SQL/PHP y `git diff --check`.
- Resultado: PASS — documentación consistente con las referencias encontradas.

## Resumen

- Rama: `dev/kerlint-database`
- Autor Git: Kerlint `<kmurillojosue75@gmail.com>`
- Área: base de datos y configuración.
- Commits: `779c454`, `58de19b`, `63a3bb7`.
- Archivos tocados: `conexion.php`, `config.php`, `.gitignore`, `docs/database-schema.md`.
- Problemas solucionados: credenciales hardcodeadas, errores de conexión expuestos, configuración no versionable y falta de documentación del esquema.
- Pruebas: lint PHP PASS en `conexion.php` y `config.php`; revisión de consultas y diff PASS.
- Pendientes: probar la conexión contra una base de datos de integración y versionar migraciones SQL formales.
