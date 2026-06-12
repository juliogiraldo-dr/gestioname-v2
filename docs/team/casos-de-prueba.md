# Casos de prueba (beta interna)

Marca cada caso al verificarlo. Si algo falla, abre un bug en [bugs.md](bugs.md).

## Super Admin
- [ ] Crear nuevo tenant desde `/superadmin/tenants/nuevo`
- [ ] Cambiar plan de un tenant (Free → Starter)
- [ ] Impersonar tenant → entrar como su admin sin contraseña
- [ ] Reset contraseña de un usuario → copiar magic link
- [ ] Activar módulo "Comunicaciones" en un tenant
- [ ] Ver auditoría en `/superadmin/auditoria`
- [ ] Ver KPIs en dashboard (MRR, tenants activos, empleados globales)

## Admin
- [ ] Crear empresa y centro de trabajo
- [ ] Asignar convenio(s) al centro de trabajo
- [ ] Crear empleado con código de fichaje (8 dígitos)
- [ ] Crear entidad (asociación) con tipos de socio y cuotas
- [ ] Dar de alta socio y registrar pago (recibo PDF)
- [ ] Exportar socios a Excel
- [ ] Aprobar solicitud de ausencia de un empleado
- [ ] Generar informe ET 34.9 en Excel
- [ ] Enviar email masivo a socios desde `/admin/comunicaciones`
- [ ] Activar recordatorio automático de cuota por entidad
- [ ] Activar módulo marca blanca y personalizar color + logo
- [ ] Ver consumo de límites del plan en `/admin/suscripcion`

## Gestoría
- [ ] Subir nómina PDF a un empleado (mes/año)
- [ ] Verificar que el empleado recibe email de aviso (Mailpit en local)
- [ ] Generar enlace de descarga público (72 h, un solo uso)
- [ ] Descargar informe de registro horario ET 34.9

## Empleado (portal `/portal`)
- [ ] Fichar entrada desde `/clock` con PIN
- [ ] Seleccionar modalidad presencial/teletrabajo
- [ ] Ver mis fichajes de la semana con total de horas
- [ ] Solicitar ausencia con fechas (date range)
- [ ] Ver estado de mi ausencia (pendiente/aprobada/rechazada)
- [ ] Descargar nómina desde `/portal/nominas`
- [ ] Editar datos personales en `/portal/datos`
- [ ] Ver datos laborales en `/portal/laboral`

## Público (sin login)
- [ ] Ver la landing en `/` (hero, planes, FAQ)
- [ ] Completar el onboarding en `/onboarding` (5 pasos) y crear cuenta
- [ ] Recibir el email de bienvenida con el enlace de acceso
- [ ] Revisar páginas legales (`/legal/privacidad`, `/legal/terminos`, `/legal/cookies`, `/legal/dpa`)
