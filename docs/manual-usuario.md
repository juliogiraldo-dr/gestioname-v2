# Gestioname v2 — Manual de usuario

> Para el equipo de Datarecover. Lenguaje no técnico. Versión beta interna · junio 2026.

## Acceso

- **URL**: https://app.gestioname.es

### Credenciales por rol (tenant demo)

| Rol | Email | Contraseña | Qué puede hacer |
|---|---|---|---|
| Administrador | admin@demo.gestioname.app | password | Todo el panel de administración |
| Gestoría | gestoria@demo.gestioname.app | password | Solo nóminas, descargas e informes |
| Super Admin | superadmin@demo.gestioname.app | password | Panel global de la plataforma |

### Cómo iniciar sesión (paso a paso)

1. Abre https://app.gestioname.es en el navegador.
2. Pulsa **Iniciar sesión** (o ve a la página de acceso).
3. Escribe tu **email** y tu **contraseña**.
4. Pulsa **Entrar**. Llegarás al panel que corresponde a tu rol.

Si el email o la contraseña no son correctos, verás el mensaje *"El email o la contraseña no son correctos."*

### Qué hacer si no puedes entrar (magic link)

Si olvidaste la contraseña, usa el **enlace de acceso por email** (magic link):

1. En la pantalla de acceso, elige **Acceder por email**.
2. Escribe tu email y pulsa enviar.
3. Recibirás un correo con un botón **Acceder**. Es de un solo uso y caduca pronto.
4. Al pulsarlo, entras sin necesidad de contraseña.

Si sigues sin poder entrar, avisa a Julio (super-admin) para que te reenvíe un enlace.

---

## Perfil: Administrador

El administrador gestiona toda la organización. El menú lateral muestra solo los módulos
**activos** del tenant.

### Configuración

Es el centro de configuración de la empresa activa (selector en la cabecera).

- **Activar/desactivar módulos**: cada módulo (RRHH, Socios, Tesorería, Contabilidad,
  Comunicaciones, Marca blanca) se activa con un interruptor. Al activarlo, aparece en el
  menú lateral; al desactivarlo, desaparece.
- **Crear empresa y centro de trabajo**:
  1. Entra en *Configuración → Empresas*.
  2. Pulsa **Nueva empresa**, rellena nombre, CIF, email y teléfono.
  3. Dentro de la empresa, añade un **centro de trabajo** (nombre, dirección, zona horaria).
- **Añadir convenio colectivo**: en *Convenios*, crea el convenio (horas anuales, días de
  vacaciones, tipo). Asígnalo a uno o varios centros desde el centro de trabajo.
- **Configurar hitos de fichaje (entrada/salida)**: en *Hitos*, crea al menos un hito de
  tipo **entrada** y otro de **salida** por empresa. Son los botones que verá el empleado al fichar.
- **Añadir festivos**: en *Festivos*, elige el año y la empresa, y añade los días festivos
  (nacionales, autonómicos o locales) marcando los centros a los que aplican.

### Empleados

- **Dar de alta un empleado nuevo**:
  1. *Empleados → Nuevo empleado*.
  2. Rellena **Nombre** y **Apellidos** (obligatorios, marcados con *).
  3. Indica email, departamento y puesto.
  4. Guarda. El botón **Crear** solo se activa con nombre y apellidos.
- **Editar datos personales y laborales**: abre la ficha del empleado y usa las pestañas
  *Datos personales* y *Laboral* (centro, convenio, fechas en formato **dd/MM/yyyy**).
- **Asignar código de fichaje de 8 dígitos**: en la ficha, campo *Código de fichaje*. Es el
  PIN que usará en el kiosk.
- **Desactivar un empleado**: en la ficha, botón **Desactivar**. Deja de aparecer en el
  listado activo y no puede fichar; sus datos se conservan.

### Fichajes

- **Ver fichajes del día**: *Fichajes* muestra la jornada de la fecha seleccionada con barra visual.
- **Navegar entre días**: usa las flechas **← Día anterior** y **Día siguiente →**.
- **Corregir un fichaje manualmente**: pulsa *Corregir* en la fila, indica la nueva hora y un
  **motivo obligatorio** (queda registrado por la normativa ET 34.9).
- **Alertas**: arriba se muestran *"X sin fichar hoy"* y *"X fichajes incompletos"* (entrada sin salida).

### Ausencias

- **Aprobar una solicitud**: en *Ausencias → Pendientes*, pulsa **Aprobar**. El empleado recibe aviso por email.
- **Ver pendientes**: pestaña *Pendientes*.
- **Crear ausencia manual**: usa el formulario indicando empleado, tipo y rango de fechas.

### Gestoría

- **Subir nómina PDF a un empleado**: *Gestoría → Nóminas*, pulsa **Subir nómina**, elige mes,
  año y el PDF. Al guardar, el empleado recibe un email "Tu nómina de [mes/año] está disponible".
- **Generar enlace de descarga (72 h, un solo uso)**: junto a cada nómina, el icono 🔗 crea un
  enlace para compartir con una gestoría externa sin darle acceso a la plataforma.
- **Exportar ET 34.9 en Excel**: en *Documentos RRHH* / *Informes*, genera el registro horario.

### Entidades y Socios

- **Crear entidad** (peña, AMPA, asociación): *Entidades → Nueva entidad* (nombre, tipo, ejercicio).
- **Dar de alta un socio**: dentro de la entidad, *Socios → Nuevo socio*.
- **Registrar pago de cuota**: en la ficha del socio, *Registrar pago* (importe, fecha, método).
- **Exportar / importar Excel**: botones de exportar lista e importar desde plantilla.
- **PDF de ficha y carnet**: desde la ficha del socio puedes generar el recibo, la ficha y el carnet.

### Tesorería

- **Registrar un gasto**: *Tesorería*, **Nuevo gasto** (categoría, importe, fecha en dd/MM/yyyy, descripción).
- **KPIs**: *saldo inicial* + *ingresos cobrados* − *gastos* = *saldo banco*; también se muestra lo *pendiente* de cobro.
- **Editar y eliminar gastos**: desde la tabla de gastos.

### Comunicaciones

- **Activar el módulo** desde *Configuración*.
- **Email masivo a socios**: filtra por estado, tipo o estado de pago, escribe asunto y cuerpo,
  usa la **vista previa** y **Enviar**. Queda en el *Historial*.
- **Recordatorio automático de cuota**: por entidad, activa el aviso e indica cuántos días antes
  del cierre del ejercicio enviarlo, con plantilla personalizable.

### Suscripción

- **Ver plan y consumo**: *Suscripción* muestra el plan actual y barras de consumo frente a los límites.
- **Solicitar upgrade**: botón **Solicitar upgrade** → formulario (nombre, email, plan) que envía
  la petición a info@datarecover.es.

---

## Perfil: Gestoría

La gestoría ve un panel reducido: **Gestoría** e **Informes**. No ve datos sensibles (DNI/IBAN)
ni puede modificar empleados, fichajes ni configuración.

- **Subir nóminas** (paso a paso):
  1. *Gestoría → Nóminas*.
  2. Busca al empleado y pulsa **Subir nómina**.
  3. Elige mes, año y el PDF; pulsa **Subir y avisar**.
- **Descargar informes de jornada**: desde *Informes*, genera el registro horario ET 34.9 (Excel/PDF).

---

## Perfil: Empleado (portal `/portal`)

- **Acceso al portal**: inicia sesión y llegarás a `/portal`.
- **Fichar desde `/clock` con PIN de 8 dígitos**: en el kiosk, introduce tu PIN, elige
  ENTRADA o SALIDA y la modalidad (Oficina/Teletrabajo).
- **Ver mis fichajes de la semana**: *Mis fichajes* muestra entrada, salida, modalidad y horas por día.
- **Solicitar ausencia**: *Ausencias → Solicitar*, elige tipo y rango de fechas.
- **Descargar nóminas**: *Mis nóminas*, botón **Descargar PDF**.

---

## Kiosk de fichaje (`/clock`)

- **Qué es**: una pantalla compartida (tablet u ordenador en la oficina) donde los empleados fichan con su PIN.
- **Configuración la primera vez**:
  1. Abre `/clock`. Verás la pantalla de configuración.
  2. Selecciona la **empresa**.
  3. Elige el **hito de ENTRADA** y el **hito de SALIDA** por su nombre.
  4. Guarda. La configuración se recuerda en ese dispositivo.
- **Día a día**: el empleado teclea su PIN de 8 dígitos, pulsa ENTRADA o SALIDA, elige
  Oficina o Teletrabajo y confirma. Si el centro lo exige, se pedirá la ubicación.

---

## Preguntas frecuentes

**¿Cómo recupero el acceso si olvidé la contraseña?**
Usa el acceso por email (magic link): recibirás un enlace de un solo uso para entrar sin contraseña.

**¿Puedo tener varias empresas en la misma cuenta?**
Sí. Se crean en *Configuración → Empresas* y se cambia entre ellas con el selector de la cabecera
(en móvil, dentro del menú lateral).

**¿Qué pasa si un empleado ficha dos veces seguidas?**
El sistema rechaza una segunda entrada sin salida (error "doble entrada") y una salida sin entrada previa.

**¿Cómo sé qué plan tengo?**
En *Suscripción* ves el plan actual y el consumo de límites.

**¿Los datos están seguros?**
Sí. Cada cliente tiene sus datos aislados (multi-tenant) y los campos sensibles (DNI, IBAN) se
guardan cifrados. La conexión es HTTPS.

**¿Puedo exportar todos mis datos?**
Sí: socios y empleados a Excel, y hay backup JSON de la entidad. Las nóminas se descargan en PDF.

**¿Qué diferencia hay entre Entidades y Empresas?**
Las **Empresas** son para RRHH/fichajes (empleados). Las **Entidades** son asociaciones/peñas con
socios y cuotas. Son independientes.

**¿Cómo activo el módulo de Comunicaciones?**
En *Configuración*, activa el interruptor de **Comunicaciones**; aparecerá en el menú lateral.

**¿El kiosk funciona en una tablet?**
Sí, `/clock` está pensado para tablet/pantalla compartida y es responsive.

**¿Puedo tener más de un administrador?**
Sí. El super-admin (o un admin) puede asignar el rol *admin* a varios usuarios del tenant.

**¿Por qué no veo los subdominios por cliente (demo.app.gestioname.es)?**
En la beta se trabaja en `app.gestioname.es` (tenant demo). Los subdominios por tenant están
pendientes del certificado wildcard en el servidor.
