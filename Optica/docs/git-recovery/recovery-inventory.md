# Inventario de recuperación de Git

Fecha de auditoría: 2026-08-20

## Alcance

La raíz correcta del proyecto es `C:/Users/kmuri/Downloads/download/htdocs/sow/Optica`. La carpeta `.git` no existía al iniciar la auditoría. El estado fue preservado antes de cualquier cambio en:

`C:/Users/kmuri/Downloads/Optica-preservation-20260820`

La copia de preservación contiene 917 archivos, igual que la raíz auditada en ese momento.

## Evidencia encontrada

| Evidencia | Ubicación | Fecha aproximada | Versión | Utilidad |
| --- | --- | --- | --- | --- |
| Estado de trabajo | `C:/Users/kmuri/Downloads/download/htdocs/sow/Optica` | 2026-08-20 | Estado actual | Baseline recuperable |
| ZIP del proyecto | `C:/Users/kmuri/Downloads/download.zip` | 2026-08-20 | Copia del estado actual | Verificación de integridad; no es snapshot anterior |
| Copia de preservación | `C:/Users/kmuri/Downloads/Optica-preservation-20260820` | 2026-08-20 | Réplica previa a Git | Respaldo de seguridad |
| SQL adyacentes | `C:/Users/kmuri/Downloads/download/htdocs/sow/*.sql` | 2026-08-20 | Datos/esquemas separados | Evidencia de base de datos, no historial de código |
| Archivos `.mwb` y `.bak` | raíz del proyecto | 2026-08-20 | Diagramas de BD | Evidencia de modelado, sin metadata Git |

## Resultado de la comparación

El contenido de `download.zip` bajo `htdocs/sow/Optica` contiene exactamente 917 archivos. La comparación SHA-256 contra la raíz actual encontró:

- agregados: 0;
- eliminados: 0;
- modificados: 0.

Por tanto, el ZIP no permite reconstruir una secuencia de commits: es una copia equivalente del estado final.

## Evidencia no encontrada

No se encontró un `.git`, bundle Git, parche, archivo diff ni una segunda copia de Óptica SOW con diferencias de contenido en las ubicaciones de trabajo revisadas. Las fechas observadas proceden de la extracción/copia y no prueban fechas originales de commits.

## Riesgos identificados

Se detectaron credenciales SMTP y configuración local incrustadas en `conexion.php`, `config.php`, `probar_mail.php`, `includes/mailer.php` y `cliente/mailer.php`. Esos archivos permanecen en el árbol local preservado, pero se excluyen del historial reconstruido mediante `.gitignore`. Deben migrarse a variables de entorno antes de publicar el repositorio.
