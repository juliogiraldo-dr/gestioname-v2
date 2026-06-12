# Módulos

Resumen de cada módulo: qué hace, quién lo usa y los flujos principales.

## Super Admin — `/superadmin`
**Quién:** equipo Datarecover (rol `super-admin`).
Gestión global de la plataforma: alta y gestión de tenants (clientes), planes y precios,
límites y overrides por tenant, impersonación, reset de contraseñas, activación de módulos,
auditoría y KPIs (MRR, tenants activos, empleados/socios globales).

## Configuración — `/admin/configuracion`
**Quién:** `admin`.
Toda la configuración de la empresa **activa** (selector en la cabecera): módulos,
empresas y grupos, centros de trabajo (con convenios y geolocalización), convenios y
tipos de ausencia, hitos de fichaje, festivos (por empresa), calendarios laborales
(plantillas de horario + asignación a días) y marca blanca (color, tipografía, logo).

## Empleados — `/admin/empleados`
**Quién:** `admin`, `rrhh-coordinator`.
Listado con filtros + ficha con pestañas (datos personales, laboral, formación,
documentos, materiales, comportamiento). Alta manual, invitación por email e
importación Excel. El convenio se filtra por el centro asignado.

## Fichajes — `/admin/fichajes` + Kiosk `/clock`
**Quién:** gestores (admin/RRHH); el kiosk lo usa cualquier empleado con su PIN.
Vista diaria con barra visual, modalidad (Oficina/Teletrabajo), correcciones y borrado
con auditoría obligatoria (ET 34.9). El **kiosk** ficha por PIN de 8 dígitos, elige
hito (entrada/salida), modalidad y, si el centro lo exige, pide geolocalización.

## Ausencias — `/admin/ausencias`
**Quién:** gestores aprueban; los empleados solicitan desde el portal.
Solicitudes pendientes (aprobar/rechazar con notificación por email), listado mensual
y resumen de vacaciones.

## Gestoría — `/admin/gestoria`
**Quién:** `admin`, `gestoria`.
Nóminas (subir PDF por empleado con mes/año → aviso automático al empleado),
documentos de RRHH (informes) y exportación a3asesor (Fase 3). Permite generar
**enlaces de descarga públicos** de un solo uso (72 h) para compartir con gestorías
externas sin darles acceso. La gestoría no ve datos sensibles (DNI/IBAN) ni modifica
empleados/fichajes/configuración.

## Entidades y Socios — `/admin/entidades`, `/admin/socios`
**Quién:** `admin`.
Entidades (asociaciones) independientes de empresa, con tipos de socio y cuotas.
Socios: ficha completa, número autonumerado, estados, pagos (recibo PDF), import/export
Excel, carnet y listado PDF, backup JSON.

## Tesorería — `/admin/tesoreria`
**Quién:** `admin`.
KPIs del ejercicio (saldo inicial + ingresos cobrados − gastos = saldo banco),
gastos por categoría y pagos de cuotas.

## Comunicaciones — `/admin/comunicaciones`
**Quién:** `admin`.
Email masivo a socios (filtros por estado, tipo y estado de pago, con vista previa e
historial), email masivo a empleados y recordatorio automático de cuota por entidad
(días antes del cierre, plantilla configurable).

## Portal del empleado — `/portal`
**Quién:** `employee`, `operator`.
Inicio con KPIs, mis fichajes (semana con horas y modalidad), mi horario, ausencias
(solicitar con rango de fechas), mis datos (editar contacto + foto), datos laborales
(contrato/convenio/horario) y mis nóminas (descarga).

## Suscripción — `/admin/suscripcion`
**Quién:** `admin`.
Plan actual, consumo frente a límites (barras de progreso) y modal de planes.
