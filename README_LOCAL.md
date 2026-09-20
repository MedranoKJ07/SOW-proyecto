# Óptica SOW en local (XAMPP)

## Requisitos

- Windows con XAMPP instalado en `C:\xampp`.
- Apache y MySQL iniciados desde el panel de XAMPP.
- PHP 8.2 o compatible con `mysqli`, `mbstring`, `openssl` y `zip`.
- Composer solo es necesario si hubiera que reconstruir `vendor/`; el proyecto ya incluye `vendor/autoload.php`.

## Instalación

1. Copia la carpeta `sow` dentro de `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL. La configuración comprobada usa MySQL en `127.0.0.1:3306`, usuario `root` y contraseña vacía.
3. Desde `C:\xampp\htdocs\sow\Optica` ejecuta:

   ```powershell
   Set-Location C:\xampp\htdocs\sow\Optica
   Get-Content -Raw .\database\setup_local.sql | & C:\xampp\mysql\bin\mysql.exe --protocol=TCP -h 127.0.0.1 -P 3306 -u root
   ```

   El instalador crea la base `optica`, las tablas, vistas, índices y datos mínimos locales. No ejecuta los SQL históricos de la raíz.

4. Abre [http://localhost/sow/Optica/](http://localhost/sow/Optica/).
5. Comprueba la base en [http://localhost/sow/Optica/Administrador/test_db.php](http://localhost/sow/Optica/Administrador/test_db.php).

Para crear o restablecer únicamente los usuarios demo en otro equipo, después de instalar el esquema ejecuta:

```powershell
Set-Location C:\xampp\htdocs\sow\Optica
Get-Content -Raw .\database\04_demo_users.sql | & C:\xampp\mysql\bin\mysql.exe --protocol=TCP -h 127.0.0.1 -P 3306 -u root
```

El script es idempotente y restablece las cuentas demo locales. Las contraseñas de desarrollo no se publican en la documentación versionada.

## Usuarios locales

El seed crea tres usuarios, todos con MFA desactivado para no depender de SMTP durante las pruebas:

- `admin`
- `doctor`
- `secretaria`

Son cuentas de desarrollo local. Define contraseñas nuevas antes de cualquier despliegue y no las subas al repositorio.

## Configuración

`Optica/conexion.php` usa por defecto `127.0.0.1`, `root`, contraseña vacía, base `optica`, puerto `3306` y `utf8mb4`. Para otro entorno se pueden definir `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` y `DB_PORT` como variables de entorno sin editar el código.

`Optica/config.php` genera `BASE_URL` como `http://localhost/sow/Optica/`. Se puede sobrescribir con la variable `BASE_URL`.

El correo es opcional en local. Si se necesita, configura `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM` y `SMTP_DOCTOR_TO` en el entorno. No coloques contraseñas SMTP en PHP ni en este README.

## Si el puerto o la contraseña de root son diferentes

- Puerto distinto: cambia `-P 3306` al importar y define `DB_PORT` con el mismo valor para Apache.
- Contraseña root: usa `-p` en el cliente MySQL para que la solicite y define `DB_PASS` en el entorno de Apache/PHP. No escribas la contraseña en los scripts SQL.

## SQL histórico

`articulos.sql`, `cliente.sql`, `proveedores.sql` y `usuarios.sql` se conservaron como referencia. Contienen comandos de inspección, datos de desarrollo y convenciones contradictorias; no forman parte de la instalación local. La fuente instalable es `Optica/database/setup_local.sql`.
