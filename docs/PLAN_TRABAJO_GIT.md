# Plan de trabajo Git

## Referencia temporal

Este plan usa como referencia inicial el período iniciado el **20 de agosto de 2026**. Las fechas aquí descritas representan planificación y documentación del proyecto; no atribuyen autoría ni fechas artificiales a commits.
## Sprints académicos previstos

Estas fechas son una planificación de trabajo y no modifican las fechas reales de Git.

| Sprint | Fechas previstas | Enfoque | Responsables principales |
| --- | --- | --- | --- |
| Sprint 1 | 20–26 de agosto de 2026 | Arquitectura, base de datos, autenticación y estructura inicial | Kerlint, Celeste |
| Sprint 2 | 27 de agosto–2 de septiembre de 2026 | Administración, inventario, facturación y pagos | Cristopher, Kerlint |
| Sprint 3 | 3–9 de septiembre de 2026 | Pacientes, citas, agenda e historial clínico | Eduardo, Celeste |
| Sprint 4 | 10–16 de septiembre de 2026 | Integración, pruebas, documentación y preparación de entrega | Todo el equipo |

## Distribución inicial

### Kerlint — `feature/kerlint-arquitectura-api`

- Arquitectura.
- API y backend.
- Configuración del repositorio y despliegue.
- Base de datos y migraciones.
- Integración frontend/backend/base de datos.

### Celeste — `feature/celeste-auth-seguridad`

- Autenticación.
- Roles y permisos.
- Seguridad y control de acceso.
- Navegación por roles.

### Cristopher — `feature/cristopher-administracion`

- Servicios y productos.
- Inventario y proveedores.
- Facturación y pagos.
- Reportes y pruebas de flujos administrativos.

### Eduardo — `feature/eduardo-clinica`

- Pacientes.
- Citas y agenda.
- Historial clínico.
- Diagnóstico y recetas.
- Validaciones y pruebas de atención.

## Orden de integración

1. Cada área implementa y prueba su trabajo en su rama `feature/*`.
2. Los cambios revisados se integran mediante Pull Request hacia `develop`.
3. `develop` se valida como sistema completo, incluyendo base de datos y documentación.
4. Una versión estable se integra mediante Pull Request hacia `main`.
