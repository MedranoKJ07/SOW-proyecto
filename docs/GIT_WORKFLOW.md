# Flujo de trabajo Git

El repositorio usa una rama estable, una rama de integración y ramas de trabajo por área:

```text
main
│
└── develop
    │
    ├── feature/kerlint-arquitectura-api
    ├── feature/celeste-auth-seguridad
    ├── feature/cristopher-administracion
    └── feature/eduardo-clinica
```

## Ramas

- `main`: versiones integradas y estables, listas para revisión o despliegue.
- `develop`: integración activa de las funcionalidades aprobadas.
- `feature/*`: trabajo aislado de cada integrante. Las ramas iniciales son:
  - `feature/kerlint-arquitectura-api`
  - `feature/celeste-auth-seguridad`
  - `feature/cristopher-administracion`
  - `feature/eduardo-clinica`

## Flujo diario

1. Cada integrante trabaja en su rama `feature/*` y no hace commits directos en `main`.
2. Mantiene la rama sincronizada con `develop` antes de iniciar o entregar trabajo.
3. Hace commits pequeños y descriptivos usando Conventional Commits cuando sea posible.
4. Publica los cambios en su propia rama con `git push -u origin <rama>`.
5. Abre un Pull Request desde `feature/*` hacia `develop` y solicita revisión.
6. `develop` se prueba como conjunto integrado.
7. Cuando existe una versión estable, se abre un Pull Request de `develop` hacia `main`.

Los conflictos se resuelven en la rama de trabajo, conservando la intención de cada área y dejando documentadas las migraciones o decisiones relevantes.
## Integración

```text
feature/* ── revisión y Pull Request ──> develop ── pruebas ──> main
```

Cada integrante trabaja individualmente en su rama de feature. El Pull Request hacia
`develop` debe incluir responsable, área, cambios, pruebas y evidencia. Después de la
revisión, `develop` se valida como conjunto; solo una versión estable y probada se
integra hacia `main`.
