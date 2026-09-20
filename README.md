# SOW Proyecto

Sistema de Gestión Clínica y Administrativa para la Óptica SOW.

Sistema web para gestionar la operación clínica y administrativa de la Óptica SOW: pacientes, citas, historial clínico, usuarios, inventario, compras, facturación y reportes.

## Estado actual

El repositorio contiene una aplicación PHP server-rendered en funcionamiento local sobre XAMPP, con scripts SQL instalables y documentación técnica. La fuente ejecutable está en `Optica/`. No se detectó un servicio API independiente; las responsabilidades de API e integración quedan documentadas para el trabajo futuro del equipo.

## Tecnologías detectadas

- PHP y HTML/CSS.
- MySQL o MariaDB mediante `mysqli`.
- Apache/XAMPP para ejecución local.
- Composer con PHPMailer y PhpSpreadsheet.
- Scripts SQL versionados en `Optica/database/`.

## Estructura principal

```text
.
├── Optica/                 # Aplicación PHP
│   ├── Administrador/      # Administración, proveedores e inventario
│   ├── cliente/            # Flujo de citas del paciente
│   ├── COntabilidad/       # Compras, pagos y reportes financieros
│   ├── database/           # Instalación, esquema, vistas y datos locales
│   ├── docs/               # Documentación técnica y evidencia de recuperación
│   └── includes/           # Autenticación y utilidades compartidas
├── docs/                   # Flujo Git, equipo y plan de trabajo
├── .github/                # Plantillas de colaboración
└── README_LOCAL.md         # Notas de ejecución en XAMPP
```

## Instalación local

1. Instala XAMPP con Apache, MySQL/MariaDB y PHP 8.2 o compatible con `mysqli`, `mbstring`, `openssl` y `zip`.
2. Ejecuta Composer dentro de `Optica/` para reconstruir dependencias si `vendor/` no está presente:

   ```powershell
   Set-Location C:\xampp\htdocs\sow\Optica
   composer install
   ```

3. Copia `Optica/.env.example` a `Optica/.env` y completa únicamente valores locales. No subas `.env`.
4. Con Apache y MySQL activos, instala la base desde `C:\xampp\htdocs\sow\Optica`:

   ```powershell
   Set-Location C:\xampp\htdocs\sow\Optica
   Get-Content -Raw .\database\setup_local.sql | & C:\xampp\mysql\bin\mysql.exe --protocol=TCP -h 127.0.0.1 -P 3306 -u root
   ```

5. Abre `http://localhost/sow/Optica/`.

La documentación local ampliada está en [README_LOCAL.md](README_LOCAL.md). Las contraseñas demo no se publican; deben definirse y cambiarse en cada entorno local.

## Integrantes

| Integrante | GitHub | Área principal |
| --- | --- | --- |
| Kerlint Josue Medrano Murillo | [@MedranoKJ07](https://github.com/MedranoKJ07) | Arquitectura, backend, API, BD e integración |
| Celeste Nahomi Ruiz Sanchez | [@Azuclaro](https://github.com/Azuclaro) | Autenticación, usuarios, roles y seguridad |
| Cristopher Edmundo Lopez Navarro | [@leninbeats](https://github.com/leninbeats) | Administración, finanzas, inventario y reportes |
| Eduardo Jose Medrano Duarte | [@EddM03](https://github.com/EddM03) | Pacientes, citas e historial clínico |

Consulta [docs/EQUIPO.md](docs/EQUIPO.md) para el detalle de responsabilidades y [CONTRIBUTING.md](CONTRIBUTING.md) para el flujo de trabajo.
