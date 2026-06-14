# Gestioname v2 — Guía de pruebas beta interna

## Instrucciones generales

- **URL**: https://app.gestioname.es
- **Cómo reportar un bug**: usa la plantilla de `docs/team/bugs.md` (Módulo, Rol, Pasos,
  Resultado esperado/obtenido, Captura, Prioridad) y compártelo por el canal acordado.
- **Credenciales de prueba**: Administrador `admin@demo.gestioname.app` / `password`;
  Gestoría `gestoria@demo.gestioname.app` / `password`; Super Admin
  `superadmin@demo.gestioname.app` / `password`.
- **Cómo marcar cada caso**: ✅ funciona · ❌ falla (anota exactamente qué pasó y los pasos).

## Módulo 1: Login y acceso
- [ ] Entrar con credenciales correctas → dashboard
- [ ] Entrar con contraseña incorrecta → mensaje de error en español
- [ ] Cerrar sesión → landing pública (no /login)
- [ ] Visitar /admin sin sesión → landing pública
- [ ] Visitar /portal sin sesión → landing pública

## Módulo 2: Configuración
- [ ] Activar módulo "Comunicaciones" → aparece en el menú lateral
- [ ] Desactivar módulo "Comunicaciones" → desaparece del menú
- [ ] Crear empresa nueva
- [ ] Crear centro de trabajo y asignarle convenio
- [ ] Añadir hito de entrada y salida
- [ ] Añadir festivo para una empresa

## Módulo 3: Empleados
- [ ] Crear empleado (nombre, apellidos, email, código fichaje 8 dígitos)
- [ ] Editar datos laborales (centro, convenio, fecha de alta en dd/MM/yyyy)
- [ ] Buscar empleado por nombre
- [ ] Exportar lista de empleados a Excel
- [ ] Desactivar empleado → desaparece del listado activo

## Módulo 4: Fichajes
- [ ] Configurar kiosk: seleccionar empresa e hitos con nombres legibles
- [ ] Fichar entrada desde /clock con PIN de prueba
- [ ] Seleccionar modalidad presencial / teletrabajo
- [ ] Ver el fichaje en /admin/fichajes del mismo día
- [ ] Navegar al día anterior y siguiente con las flechas ← →
- [ ] Corregir manualmente la hora de un fichaje
- [ ] Verificar alerta "sin fichar hoy"

## Módulo 5: Ausencias
- [ ] Solicitar ausencia desde el portal con fechas
- [ ] Aprobar la solicitud desde admin
- [ ] Rechazar una solicitud con motivo
- [ ] Crear ausencia manual para un empleado

## Módulo 6: Gestoría
- [ ] Subir PDF de nómina a un empleado (mes y año)
- [ ] Botón "Seleccionar PDF" en español (no "Choose File")
- [ ] Generar enlace de descarga y verificar que abre el PDF
- [ ] Descargar ET 34.9 en Excel con rango de fechas
- [ ] Verificar que la pestaña "Exportación a3asesor" descarga el fichero

## Módulo 7: Entidades y Socios
- [ ] Crear entidad tipo "Peña"
- [ ] Crear 3 socios con tipos distintos (Adulto, Juvenil, De Honor)
- [ ] Registrar pago de cuota → el socio pasa a "pagado"
- [ ] Verificar orden: socios ordenados por nº de socio (1, 2, 3...)
- [ ] Exportar lista de socios a Excel
- [ ] Ver PDF de ficha de socio y carnet

## Módulo 8: Tesorería
- [ ] Registrar gasto nuevo con categoría y fecha
- [ ] Verificar fecha en formato dd/MM/yyyy (no ISO ni US)
- [ ] Comprobar que el saldo banco se actualiza
- [ ] Editar y eliminar un gasto

## Módulo 9: Comunicaciones
- [ ] Activar el módulo en Configuración
- [ ] Enviar email de prueba a los socios de una entidad
- [ ] Configurar el recordatorio de cuota

## Módulo 10: Portal del empleado
- [ ] /portal/datos sin empleado vinculado → mensaje "No tienes ningún empleado vinculado a esta cuenta." + botón "Ir al inicio"
- [ ] /portal/fichajes sin empleado → mismo mensaje
- [ ] Acceder como empleado real → ver datos correctos

## Módulo 11: Suscripción
- [ ] Ver el plan actual y las barras de consumo
- [ ] "Solicitar upgrade" → formulario de contacto → el email llega a info@datarecover.es

## Módulo 12: Super-admin (solo Julio)
- [ ] KPIs en /superadmin (MRR, tenants, empleados, socios)
- [ ] Crear un tenant nuevo
- [ ] Impersonar un tenant
- [ ] Cambiar el plan de un tenant
- [ ] Activar/desactivar un módulo de un tenant
- [ ] Ver el certificado TLS (válido hasta 10/09/2026)
- [ ] Ver la auditoría de acciones

## Registro de incidencias

| Módulo | Descripción | Severidad (Alta/Media/Baja) | Fecha | Resuelto |
|---|---|---|---|---|
|  |  |  |  |  |
|  |  |  |  |  |
|  |  |  |  |  |
