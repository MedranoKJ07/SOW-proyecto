# Equipo y responsabilidades

La división de responsabilidades evita solapamientos y facilita la revisión cruzada. Una persona puede apoyar otra área, pero cada cambio debe tener un responsable claro.

| Integrante | GitHub | Área principal |
| --- | --- | --- |
| Kerlint Josue Medrano Murillo | @MedranoKJ07 | Arquitectura, API, BD e integración |
| Celeste Nahomi Ruiz Sanchez | @Azuclaro | Autenticación, usuarios, roles y seguridad |
| Cristopher Edmundo Lopez Navarro | @leninbeats | Administración, facturación, inventario y reportes |
| Eduardo Jose Medrano Duarte | @EddM03 | Pacientes, citas e historial clínico |
## Configuración de identidad y rama

Estos comandos son una referencia para cuando cada integrante trabaje realmente en su rama. Documentarlos no fabrica autoría ni commits.

### Kerlint Josue Medrano Murillo

```powershell
git config --local user.name "Kerlint Josue Medrano Murillo"
git config --local user.email "kmurillojosue75@gmail.com"
git switch feature/kerlint-arquitectura-api
```

### Celeste Nahomi Ruiz Sanchez

```powershell
git config --local user.name "Celeste Nahomi Ruiz Sanchez"
git config --local user.email "nohemiruiz705@gmail.com"
git switch feature/celeste-auth-seguridad
```

### Cristopher Edmundo Lopez Navarro

```powershell
git config --local user.name "Cristopher Edmundo Lopez Navarro"
git config --local user.email "leninbeats@gmail.com"
git switch feature/cristopher-administracion
```

### Eduardo Jose Medrano Duarte

El correo de Eduardo queda pendiente de verificación y no se inventa en esta documentación.

```powershell
git config --local user.name "Eduardo Jose Medrano Duarte"
git config --local user.email "CORREO_VERIFICADO_DE_EDUARDO"
git switch feature/eduardo-clinica
```

## Detalle por área

- **Kerlint:** arquitectura, backend, API, base de datos, migraciones, integración frontend/backend/BD, documentación de API y despliegue.
- **Celeste:** autenticación, usuarios, roles, permisos, seguridad, control de acceso, navegación por roles y consistencia documental.
- **Cristopher:** servicios, productos, inventario, proveedores, compras, facturación, pagos, reportes y pruebas administrativas.
- **Eduardo:** pacientes, citas, agenda, historial clínico, diagnóstico, recetas, validaciones clínicas y pruebas de atención.
