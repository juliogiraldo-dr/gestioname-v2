# Acceso y arranque

## URLs

| Entorno | URL |
|---|---|
| Local | http://localhost:3000 |
| Producción | https://app.gestioname.es |
| Plataforma (auto) | https://test-julio-gestioname-app.deploy.datarecover.cloud |

> **Producción en `app.gestioname.es`** (dominio propio, HTTPS Let's Encrypt). El apex sirve
> la landing y el tenant **demo** (vía `DEFAULT_TENANT=demo`). Login del equipo:
> `admin@demo.gestioname.app` / `password`; super-admin `superadmin@demo.gestioname.app` → `/superadmin`.
>
> **Subdominios de tenant** (`demo.app.gestioname.es`, `datarecover.app.gestioname.es`):
> el DNS resuelve, pero la plataforma solo emite certificado para **un** hostname (HTTP-01,
> sin wildcard), así que esos subdominios aún **no tienen TLS**. Multi-tenant por subdominio
> queda pendiente de un **certificado wildcard `*.app.gestioname.es`** (DNS-01) en la plataforma.
> Detalle y pasos en `secretos.md`.

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
  login de super-admin/admin demo correcto **incluso sin cabecera** de tenant.
- **Resolución de tenant sin wildcard DNS**: el backend lleva `DEFAULT_TENANT=demo`, así que
  el `TenantMiddleware` resuelve el tenant `demo` cuando no hay subdominio ni cabecera. Además
  el frontend manda `X-Tenant-ID: demo` (imagen construida con `NEXT_PUBLIC_TENANT=demo`).
  Cuando exista `*.gestioname.app`, quitar `DEFAULT_TENANT` y reconstruir el frontend con
  `NEXT_PUBLIC_TENANT` vacío.
- **Aprendizajes de despliegue**:
  - Las imágenes desplegadas se fijan al commit (SHA), no a `:latest`.
  - Para actualizar producción hay que **recrear** la app (`delete` + `create`) con las
    imágenes del nuevo SHA: un `redeploy` sobre `:latest` no recicla los contenedores ni
    vuelve a descargar la imagen. Las migraciones y el seed (idempotente) corren solos en
    el arranque del backend, así que un arranque limpio re-siembra el tenant demo.

## Activar el wildcard `*.app.gestioname.es` (paso de infra en el Traefik del borde)

El certificado wildcard ya está subido y validado en el panel (`/superadmin → TLS/Certificado`),
pero el **Traefik del borde de la plataforma** (deploy.datarecover.cloud) es quien termina el TLS
de cara a Internet y **no lee** el cert guardado en el backend. Para servir los subdominios de
tenant hay que instalarlo en ese Traefik (requiere acceso al servidor del deploy):

1. Copiar al host del Traefik del borde el `fullchain.pem` y `privkey.pem` del wildcard
   (los de Plesk; los mismos que se pegaron en el panel), p. ej. en `/certs/gestioname/`.
2. Añadir un fichero de **configuración dinámica** de Traefik (file provider), p. ej.
   `/etc/traefik/dynamic/gestioname.yml`:
   ```yaml
   tls:
     certificates:
       - certFile: /certs/gestioname/fullchain.pem
         keyFile:  /certs/gestioname/privkey.pem
   ```
   Así Traefik sirve ese cert para cualquier SNI que case `*.app.gestioname.es` o
   `app.gestioname.es`, sin pedir ACME.
3. Asegurar el **router** de los subdominios → la app `gestioname` (servicio frontend, :3000),
   regla `HostRegexp(^.+\.app\.gestioname\.es$)` además del apex `Host(app.gestioname.es)`.
4. Avisar: entonces, desde el MCP, se cambia `public_hostname` a `*.app.gestioname.es` (+ apex)
   y se quita `DEFAULT_TENANT` para resolver el tenant solo por subdominio.

> Mientras esto no esté, producción funciona en el **apex `app.gestioname.es`** (tenant demo
> por `DEFAULT_TENANT`); `demo.app...`/`datarecover.app...` dan error de TLS.

## Incidencias del despliegue (registrar aquí)

> Si un paso del despliegue por MCP falla, anótalo aquí con fecha y el error, para
> retomarlo. _(Sin incidencias abiertas.)_
