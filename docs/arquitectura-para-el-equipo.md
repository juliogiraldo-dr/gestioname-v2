# Cómo está montado Gestioname (sin tecnicismos)

## Qué es un "tenant"

Cada cliente de Gestioname es un **tenant**. Sus datos (empleados, socios, fichajes,
contabilidad…) están **completamente aislados** de los del resto: es como si cada cliente
tuviera su propio "cajón" cerrado dentro de la base de datos. Nadie de un tenant puede ver
los datos de otro.

## El tenant "demo"

`demo` es el tenant de **pruebas**. Sirve para que el equipo trastee sin miedo: crear
empleados, fichar, dar de alta socios, registrar gastos… Si algo se rompe o se llena de datos
de prueba, no afecta a ningún cliente real. Es el tenant que se usa durante la beta interna en
`app.gestioname.es`.

## Cómo se actualiza la aplicación

1. El código se guarda en **Git** y se sube a **GitHub** (`github.com/juliogiraldo-dr/gestioname-v2`).
2. Al fusionar a la rama `main`, **GitHub Actions** construye automáticamente las imágenes de la
   aplicación (tarda unos **5-8 minutos**).
3. Después se hace un **redespliegue manual** desde la plataforma de Datarecover, que arranca la
   versión nueva. Al arrancar, la aplicación aplica sola las actualizaciones de base de datos y
   los datos base (planes, tenant demo).

**Cuándo hay que hacer algo manual**: el redespliegue. El resto (construir, migrar, sembrar) es automático.

## Qué pasa si algo falla

- **Comprobar el estado** del servicio: https://app.gestioname.es/health → debe devolver `{"status":"ok"}`.
- **A quién avisar**: Julio.
- **Qué información incluir**: la URL donde ocurrió, tu rol, los pasos para reproducirlo y la hora aproximada.

## Los datos y las copias de seguridad

- Los datos se guardan en una base de datos **PostgreSQL** dentro de la infraestructura de Datarecover.
- Hay un servicio gestionado de base de datos preparado con **copias de seguridad diarias**.
- Si se pierde algo, se restaura desde la copia más reciente. Además, cada entidad de socios puede
  exportarse a un **backup JSON** y los listados a **Excel**, como respaldo adicional manual.

> Nota de la beta: la aplicación corre con su base de datos en el mismo despliegue. Al recrear el
> despliegue se re-siembra el tenant demo. Antes de meter datos reales de clientes se migrará a la
> base de datos gestionada con backups diarios.
