# Acceso y arranque

## URLs

| Entorno | URL |
|---|---|
| Local | http://localhost:3000 |
| Producción | https://test-julio-gestioname-app.deploy.datarecover.cloud |

> En producción el tenant de demostración se resuelve por cabecera (`X-Tenant-ID: demo`),
> a la espera del dominio propio `*.gestioname.app`.

## Credenciales por rol (tenant demo)

| Rol | Email | Contraseña |
|---|---|---|
| Super Admin | superadmin@demo.gestioname.app | password |
| Admin | admin@demo.gestioname.app | password |
| Gestoría | gestoria@demo.gestioname.app | password |
| Empleado | *(crear uno con PIN de fichaje `12345678` para pruebas)* | — (entra por magic link / portal) |

> El empleado de pruebas se crea desde **Admin → Empleados → Nuevo** asignándole el
> código de fichaje `12345678`. Para el portal, invítalo por email (magic link).

## Arranque en local

```bash
docker compose up -d          # levanta backend, frontend, postgres, redis, mailpit
# Abre http://localhost:3000
```

- Si algo no carga o se ve datos viejos: **hard refresh** con `Ctrl+Shift+R`.
- Los emails en local se ven en **Mailpit**: http://localhost:8025

## Gotchas conocidos

- **opcache (backend)**: si se cambia código PHP del backend y no se refleja, reinicia:
  ```bash
  docker compose restart backend
  ```
- **Rutas nuevas (frontend)**: una página/ruta nueva da 404 hasta reiniciar el contenedor
  `frontend` (el hot-reload solo cubre archivos existentes).
- **Tenant en local**: sin subdominio real, el tenant se fija por `NEXT_PUBLIC_TENANT=demo`
  (cabecera `X-Tenant-ID`).

## Estado del despliegue

- **Producción ACTIVA y verificada**: `GET /health` = `{status: ok}` (BD, Redis, cola) y
  login del admin demo correcto. Las imágenes desplegadas se fijan al commit (SHA), no a `:latest`.
- **Aprendizaje**: para actualizar producción, **recrear** la app con las imágenes fijadas al
  nuevo SHA (un `redeploy` sobre `:latest` no recicla los contenedores ni vuelve a descargar
  la imagen). Las migraciones y el seed corren solos en el arranque del backend.

## Incidencias del despliegue (registrar aquí)

> Si un paso del despliegue por MCP falla, anótalo aquí con fecha y el error, para
> retomarlo. _(Sin incidencias abiertas.)_
