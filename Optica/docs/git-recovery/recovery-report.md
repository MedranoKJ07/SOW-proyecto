# Informe final de recuperación de Git

Fecha: 2026-08-20

## Motivo y preservación

La carpeta `.git` original no estaba disponible. La raíz correcta es `C:/Users/kmuri/Downloads/download/htdocs/sow/Optica`. Antes de inicializar Git se creó una copia exacta de 917 archivos en `C:/Users/kmuri/Downloads/Optica-preservation-20260820`.

## Evidencia revisada

Se revisaron la raíz, la carpeta superior, `download.zip`, los SQL adyacentes, los diagramas `.mwb`/`.bak` y las ubicaciones de trabajo relacionadas. `download.zip` contiene exactamente los 917 archivos de la raíz bajo `htdocs/sow/Optica`; la comparación SHA-256 encontró cero agregados, eliminados o modificados. No se encontró `.git`, bundle, parche, diff ni snapshot anterior con diferencias verificables.

## Historial reconstruido

No fue posible recuperar commits, fechas, ramas, mensajes ni autoría originales. Por ello no se dividió artificialmente el árbol por módulos o personas.

El historial visible contiene un único baseline de código (el commit cuyo mensaje es `chore(project): restaurar código fuente después de pérdida del historial Git`) y commits posteriores de documentación. Los commits documentales no representan versiones históricas del código. La credencial SMTP que había quedado en el baseline fue eliminada del historial local; también se limpiaron referencias auxiliares, reflogs y objetos no alcanzables. Se conservó una copia previa en `C:/Users/kmuri/Downloads/Optica-git-before-secret-scrub-20260820`.

## Autoría

No existe evidencia suficiente para atribuir cambios individuales a Kerlint, Celeste, Eduardo o Cristopher. La identidad Git usada es neutral y está configurada únicamente a nivel local del repositorio; no se usó `git config --global`.

## Seguridad y dependencias

Se detectó configuración SMTP y local sensible en `conexion.php`, `config.php`, `probar_mail.php`, `includes/mailer.php` y `cliente/mailer.php`. Permanecen en el entorno local y en la copia de preservación, pero están excluidos mediante `.gitignore`. Las contraseñas SMTP rastreadas en `Enviar_citas.php` y `recuperar.php` fueron reemplazadas por `getenv('SMTP_PASSWORD')` y el historial local fue saneado. Se añadió `.env.example` sin valores reales. `composer.json` y `composer.lock` se conservaron; `vendor/`, SQL locales, logs y backups se excluyen del historial.

Validaciones: lint PHP sin errores en 73 archivos rastreados; `composer.json` válido, con advertencia no bloqueante por licencia no declarada.

## Ramas y publicación

La rama principal es `main`. Las ramas de continuación se crearon desde el estado final de `main`:

- `dev/kerlint-database`
- `dev/celeste-auth`
- `dev/eduardo-clinical`
- `dev/cristopher-admin`

No se ejecutó `git push` ni `git push --force`.

## Limitaciones

No fue posible recuperar fechas, hashes, ramas, mensajes ni autoría del repositorio perdido. Los commits futuros deberán corresponder a cambios reales y verificables.
