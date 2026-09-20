# Contribuir al proyecto

## Flujo de ramas

- No trabajes directamente en `main`.
- Desarrolla desde la rama `feature/*` que corresponda a tu área.
- Sincroniza tu rama con `develop` antes de abrir un Pull Request.
- Publica la rama y abre el Pull Request hacia `develop`.
- La integración final hacia `main` se realiza desde `develop` después de probar la versión integrada.

## Commits

Usa mensajes pequeños, descriptivos y, cuando sea posible, Conventional Commits:

```text
feat(auth): implementar control de acceso por roles
fix(api): corregir validación de parámetros
docs(api): documentar endpoints disponibles
test(citas): validar disponibilidad de agenda
```

## Antes del Pull Request

- Ejecuta las pruebas y validaciones disponibles localmente.
- Revisa el diff completo y elimina archivos temporales.
- No subas secretos, tokens, contraseñas ni archivos `.env`.
- Si cambias el esquema, las columnas, claves o índices, incluye la migración o el SQL correspondiente y documenta cómo se probó.
- Actualiza la documentación afectada.

Usa la plantilla de Pull Request y describe evidencia verificable del cambio.
