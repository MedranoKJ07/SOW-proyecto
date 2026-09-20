# Usuarios demo locales

Estas cuentas pueden crearse en la base local `optica` para demostración:

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin` | Se define localmente |
| Doctor | `doctor` | Se define localmente |
| Secretaría | `secretaria` | Se define localmente |

Inicio de sesión: http://localhost/sow/Optica/login.php

Son credenciales únicamente para desarrollo local. MFA está desactivado para estos usuarios demo.

Para recrearlos en otro equipo, ejecuta `Optica/database/04_demo_users.sql` después de `Optica/database/setup_local.sql`.

Las contraseñas de desarrollo no se almacenan en esta documentación ni deben subirse a Git.
