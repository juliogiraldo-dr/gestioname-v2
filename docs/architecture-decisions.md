# Architecture Decision Records — Gestioname v2

> Un ADR por decisión significativa. No borrar — solo añadir.
> Estado: `aceptada` | `en revisión` | `reemplazada por ADR-XXX`

---

## ADR-001 — Stack tecnológico: Laravel + React/Next.js

**Fecha**: Junio 2026
**Estado**: aceptada

### Contexto
Necesitamos elegir stack para un SaaS multi-tenant que reemplace GME (GestionTime/Avanzadi).
Requisito: sin dependencias Microsoft, desplegable en infraestructura propia Linux/Docker.

### Decisión
Backend: **Laravel 11 / PHP 8.3**.
Frontend: **React 18 + Next.js 14** con App Router.

### Justificación
- Laravel: ecosistema maduro para SaaS (Sanctum, Spatie Permission, DomPDF, PhpSpreadsheet, Jobs, Events). Menor curva de aprendizaje que alternativas. PHP 8.3 con tipado estricto es production-ready.
- Next.js: SSR necesario para el portal del socio (SEO, rendimiento en móvil). App Router da server components que reducen JS en cliente.
- Alternativa descartada: Node (Fastify) + TypeScript en backend — menor ecosistema para PDF, Excel, SEPA, integración a3asesor.

### Consecuencias
- El equipo necesita dominar PHP moderno y React/TypeScript.
- Dos lenguajes en el monorepo (PHP + TypeScript).

---

## ADR-002 — Multi-tenancy: schemas PostgreSQL separados

**Fecha**: Junio 2026
**Estado**: aceptada

### Contexto
Necesitamos aislar los datos de cada empresa/entidad cliente.
Opciones: (a) columna tenant_id en todas las tablas, (b) schemas separados, (c) bases de datos separadas.

### Decisión
**Schemas PostgreSQL separados** por tenant.

### Justificación
- Opción (a) tenant_id: riesgo de fuga de datos si se olvida un WHERE. Requiere Row Level Security o scoping global en todos los queries.
- Opción (b) schemas: aislamiento real a nivel de BD, sin riesgo de fuga por error de programación. Migrations simples. Backup por schema. Sin overhead de múltiples conexiones.
- Opción (c) bases de datos: operacionalmente complejo, coste de infraestructura alto.
- Laravel tiene soporte nativo para múltiples schemas PostgreSQL mediante la configuración de `search_path` en la conexión.

### Consecuencias
- Comando Artisan `migrate:tenants` necesario para migraciones batch.
- El `TenantMiddleware` configura el `search_path` de PostgreSQL en cada request.
- Onboarding de nuevo tenant = crear schema + ejecutar migrations (~3 segundos).

---

## ADR-003 — Autenticación: Sanctum + Magic Link

**Fecha**: Junio 2026
**Estado**: aceptada

### Contexto
El sistema tiene múltiples tipos de usuario con diferentes patrones de acceso:
- Empleados: acceden ocasionalmente al portal, no recuerdan contraseñas.
- Socios de asociaciones: usuarios aún más ocasionales.
- Administradores: acceso frecuente, preferirán contraseña.

### Decisión
**Laravel Sanctum** para tokens API. **Magic Link** (enlace de un solo uso por email) como método primario para empleados y socios. Email + password como alternativa para administradores.

### Justificación
- Magic link elimina gestión de contraseñas para el 80% de usuarios (empleados, socios).
- Sanctum tokens son suficientes para SPA/móvil — no necesitamos OAuth completo (Passport).
- Rate limiting nativo en Laravel para prevenir abuso.

### Consecuencias
- Se necesita Postmark o SMTP fiable para el envío de magic links.
- Los magic links expiran en 15 minutos.
- Implementar OAuth social (Google) en Fase 2 si hay demanda.

---

## ADR-004 — Generación de PDFs: DomPDF

**Fecha**: Junio 2026
**Estado**: aceptada

### Contexto
Necesitamos generar PDFs para: registro horario (ET 34.9), recibos de cuota, fichas de socio, nóminas.

### Decisión
**Laravel DomPDF** (barryvdh/laravel-dompdf).

### Justificación
- Integración nativa con Laravel Blade — mismas plantillas que el frontend.
- No requiere proceso externo (vs Puppeteer que necesita Chrome headless).
- Suficiente para tablas y documentos con layout simple — no necesitamos CSS Grid complejo en los PDFs.
- Alternativa descartada: Puppeteer — añade dependencia de Node + Chrome al servidor PHP.

### Consecuencias
- DomPDF tiene limitaciones con CSS moderno. Usar CSS básico (float, tabla) en las plantillas Blade de PDFs.
- Para PDFs complejos con estilos avanzados, valorar wkhtmltopdf en Fase 2.

---

## ADR-005 — Registro de jornada: inmutabilidad estricta

**Fecha**: Junio 2026
**Estado**: aceptada

### Contexto
El artículo 34.9 ET establece que el registro de jornada debe conservarse mínimo 4 años y ser accesible para representantes sindicales e Inspección de Trabajo. Las correcciones son legalmente posibles pero deben quedar registradas.

### Decisión
La tabla `attendances` es **de solo escritura**. Nunca se actualiza ni borra un registro.
Las correcciones se registran en `attendance_corrections` y crean un nuevo registro `attendances` con los datos corregidos. El registro original queda visible en el historial.

### Justificación
- Cumplimiento ET 34.9 y criterio de Inspección de Trabajo.
- Trazabilidad completa ante litigios laborales.
- Simplicidad: no hay lógica de "versiones" — hay registros originales y correcciones.

### Consecuencias
- La tabla `attendances` crecerá continuamente (no se borran registros).
- Las consultas de "fichaje actual del día" deben filtrar correctamente considerando las correcciones.
- Añadir política de particionado de tabla por año en Fase 2 si el volumen lo requiere.

---

## ADR-006 — Exportación a3asesor: servicio dedicado SuenlaceExportService

**Fecha**: Junio 2026
**Estado**: aceptada

### Contexto
El formato suenlace.dat es un fichero ASCII de posición fija de 512 bytes por registro. Es complejo y con muchos tipos de registro distintos.

### Decisión
Implementar como **servicio independiente** `SuenlaceExportService` con builders por tipo de registro. No acoplar la lógica de exportación a los controllers ni a los modelos.

### Justificación
- El formato es estable (especificación Wolters Kluwer sin cambios en años).
- Un servicio independiente facilita el testing con fixtures.
- Distintos orígenes (contabilidad, nóminas) pueden usar el mismo servicio.

### Consecuencias
- Tests con fixtures reales de a3asesor son imprescindibles antes de usar en producción.
- El servicio debe validar que cada registro tiene exactamente 512 bytes.

---

## ADR-007 — Frontend: Tailwind CSS, sin CSS Modules

**Fecha**: Junio 2026
**Estado**: aceptada

### Decisión
**Tailwind CSS** para todos los estilos. Sin CSS Modules, sin styled-components, sin Emotion.

### Justificación
- Consistencia con el ecosistema Next.js moderno.
- Purge automático en producción — bundle CSS mínimo.
- El equipo conoce Tailwind.

### Consecuencias
- Las clases en el JSX pueden ser verbosas. Usar `clsx` para condicionales.
- Configurar el `tailwind.config.js` con los colores corporativos de Datarecover.
- Componentes reutilizables en `/frontend/components/ui/`.

---

## ADR-008 — CI/CD: GitHub Actions + deploy directo

**Fecha**: Junio 2026
**Estado**: aceptada

### Decisión
**GitHub Actions** para CI (tests + lint en cada PR) y CD (deploy a staging/producción en merge a `main`).

### Pipeline
```
PR abierto → lint PHP (pint) + lint TS (eslint) + tests PHPUnit → ✓
Merge a main → build Docker → push registry → deploy Traefik → health check
```

### Consecuencias
- Necesario secreto `DEPLOY_SSH_KEY` en GitHub para acceso al servidor.
- El servidor necesita Docker Registry accesible (usar GitHub Container Registry o registry propio).
