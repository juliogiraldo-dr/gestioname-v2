// Utilidades compartidas. Formato de fechas en español (dd/MM/yyyy) en toda la app.

type DateInput = string | number | Date | null | undefined;

function toDate(value: DateInput): Date | null {
  if (value === null || value === undefined || value === "") return null;
  const d = value instanceof Date ? value : new Date(value);
  return Number.isNaN(d.getTime()) ? null : d;
}

/** Fecha en formato dd/MM/yyyy (o «—» si no hay valor válido). */
export function formatDate(value: DateInput): string {
  const d = toDate(value);
  if (!d) return "—";
  return d.toLocaleDateString("es-ES", { day: "2-digit", month: "2-digit", year: "numeric" });
}

/** Fecha y hora en formato dd/MM/yyyy HH:mm. */
export function formatDateTime(value: DateInput): string {
  const d = toDate(value);
  if (!d) return "—";
  return d.toLocaleString("es-ES", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

/** Solo hora HH:mm. */
export function formatTime(value: DateInput): string {
  const d = toDate(value);
  if (!d) return "—";
  return d.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });
}
