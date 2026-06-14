# Plan de beta interna — Gestioname v2

## Objetivo

Validar el MVP con el equipo de Datarecover antes del lanzamiento externo: comprobar que los
flujos diarios (fichaje, ausencias, socios, nóminas, tesorería) funcionan de extremo a extremo y
detectar bugs y fricciones de uso.

## Participantes sugeridos (4 roles)

- **Julio** — admin + super-admin (configuración inicial y pruebas técnicas).
- **Persona de RRHH** — fichajes, ausencias y empleados.
- **Gestoría / contable** — nóminas, exportaciones y registro horario ET 34.9.
- **Otro miembro** — socios y tesorería (simular una peña o asociación).

## Calendario propuesto (2 semanas)

| Días | Actividad |
|---|---|
| 1-2 | Configuración: empresa, empleados, centros, hitos |
| 3-5 | Fichajes diarios reales del equipo |
| 6-7 | Socios y tesorería |
| 8-9 | Gestoría: nóminas y exportaciones |
| 10 | Revisión de bugs y priorización |

## Criterios de éxito

- Menos de **3 bugs críticos** sin resolver al finalizar.
- El equipo completa el checklist de pruebas **sin ayuda**.
- Flujo de **fichaje diario sin incidencias durante 5 días**.
- Al menos **1 exportación ET 34.9 correcta**.

## Qué NO probar en beta

- **Pagos con Stripe** (no activado todavía).
- **Subdominios por tenant** (`demo.app.gestioname.es`): pendientes del certificado wildcard en Traefik.
- **Multiidioma** y **app nativa iOS/Android**.

## Cómo reportar bugs

1. Abre `docs/team/bugs.md`.
2. Copia la plantilla.
3. Rellena todos los campos (módulo, rol, pasos, resultado esperado/obtenido, captura, prioridad).
4. Comunícalo por el canal acordado (Slack/Teams/email).
