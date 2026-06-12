"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { Button, Field, Modal, SelectField, Spinner, Toggle } from "@/components/ui";
import type { Template } from "./sections";

type CalendarDay = {
  id: string;
  date: string;
  schedule_template_id: string | null;
  template?: { id: string; name: string; color: string } | null;
};
type CalendarDetailData = { id: string; year: number; days: CalendarDay[] };

const MONTHS = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
// 1=lunes … 7=domingo (coincide con el backend).
const WEEKDAYS: ReadonlyArray<readonly [number, string]> = [
  [1, "L"], [2, "M"], [3, "X"], [4, "J"], [5, "V"], [6, "S"], [7, "D"],
];

/** Convierte un Date de JS (0=domingo) al esquema backend (1=lunes … 7=domingo). */
function isoWeekday(d: Date): number {
  const js = d.getDay();
  return js === 0 ? 7 : js;
}

function pad(n: number): string {
  return String(n).padStart(2, "0");
}

export function CalendarDetail({ calendarId, calendarName, year, templates, onClose, onChanged }: {
  calendarId: string;
  calendarName: string;
  year: number;
  templates: Template[];
  onClose: () => void;
  onChanged: () => void;
}) {
  const [detail, setDetail] = useState<CalendarDetailData | null>(null);
  const [month, setMonth] = useState(new Date().getMonth()); // 0–11
  const [templateId, setTemplateId] = useState(templates[0]?.id ?? "");
  const [rangeFrom, setRangeFrom] = useState("");
  const [rangeTo, setRangeTo] = useState("");
  const [weekdays, setWeekdays] = useState<number[]>([1, 2, 3, 4, 5]);
  const [includeHolidays, setIncludeHolidays] = useState(false);
  const [busy, setBusy] = useState(false);
  const toast = useToast();

  const load = useCallback(async () => {
    const res = await api<{ data: CalendarDetailData }>(`/calendars/${calendarId}`);
    setDetail(res.data);
  }, [calendarId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  function dayMap(): Map<string, CalendarDay> {
    const m = new Map<string, CalendarDay>();
    for (const d of detail?.days ?? []) m.set(d.date, d);
    return m;
  }

  async function fillManual(dates: string[]) {
    if (!templateId || dates.length === 0) return;
    setBusy(true);
    try {
      await api(`/calendars/${calendarId}/fill-manual`, { method: "POST", body: { dates, schedule_template_id: templateId } });
      await load();
      onChanged();
      toast.success(dates.length === 1 ? "Día asignado." : `${dates.length} días asignados.`);
    } catch (e) { toast.error(e instanceof ApiError ? e.message : "Error"); } finally { setBusy(false); }
  }

  function assignRange() {
    if (!rangeFrom || !rangeTo || !templateId) return;
    const dates: string[] = [];
    const start = new Date(`${rangeFrom}T00:00:00`);
    const end = new Date(`${rangeTo}T00:00:00`);
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
      dates.push(`${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`);
    }
    void fillManual(dates);
  }

  async function fillQuick() {
    if (!templateId || weekdays.length === 0) return;
    setBusy(true);
    try {
      const res = await api<{ days_filled: number }>(`/calendars/${calendarId}/fill-quick`, {
        method: "POST",
        body: {
          weekdays,
          months: Array.from({ length: 12 }, (_, i) => i + 1),
          schedule_template_id: templateId,
          include_holidays: includeHolidays,
        },
      });
      await load();
      onChanged();
      toast.success(`Llenado rápido: ${res.days_filled} días.`);
    } catch (e) { toast.error(e instanceof ApiError ? e.message : "Error"); } finally { setBusy(false); }
  }

  async function clearAll() {
    setBusy(true);
    try {
      await api(`/calendars/${calendarId}/clear`, { method: "DELETE" });
      await load();
      onChanged();
      toast.success("Calendario vaciado.");
    } catch (e) { toast.error(e instanceof ApiError ? e.message : "Error"); } finally { setBusy(false); }
  }

  function toggleWeekday(w: number) {
    setWeekdays((prev) => (prev.includes(w) ? prev.filter((x) => x !== w) : [...prev, w]));
  }

  const calendarYear = detail?.year ?? year;
  const days = dayMap();
  const firstOfMonth = new Date(calendarYear, month, 1);
  const offset = isoWeekday(firstOfMonth) - 1; // celdas en blanco antes del día 1
  const daysInMonth = new Date(calendarYear, month + 1, 0).getDate();

  return (
    <Modal title={`Calendario · ${calendarName} (${calendarYear})`} onClose={onClose}>
      {!detail ? <Spinner /> : (
        <div className="space-y-5">
          {/* Plantilla activa + mes */}
          <div className="flex flex-wrap items-end gap-3">
            <SelectField
              label="Plantilla a asignar"
              value={templateId}
              onChange={setTemplateId}
              className="w-56"
              options={templates.length ? templates.map((t) => [t.id, t.name] as const) : [["", "Sin plantillas"]]}
            />
            <SelectField label="Mes" value={String(month)} onChange={(v) => setMonth(Number(v))} className="w-40" options={MONTHS.map((m, i) => [String(i), m] as const)} />
          </div>

          {templates.length === 0 && <p className="text-sm text-amber-700">Crea primero una plantilla de horario para poder asignar días.</p>}

          {/* Rejilla del mes */}
          <div>
            <div className="mb-1 grid grid-cols-7 gap-1 text-center text-xs font-medium text-ink-soft">
              {WEEKDAYS.map(([, l]) => <span key={l}>{l}</span>)}
            </div>
            <div className="grid grid-cols-7 gap-1">
              {Array.from({ length: offset }).map((_, i) => <span key={`b${i}`} />)}
              {Array.from({ length: daysInMonth }, (_, i) => i + 1).map((day) => {
                const dateStr = `${calendarYear}-${pad(month + 1)}-${pad(day)}`;
                const assigned = days.get(dateStr);
                const color = assigned?.template?.color;
                return (
                  <button
                    key={dateStr}
                    type="button"
                    disabled={busy || !templateId}
                    onClick={() => void fillManual([dateStr])}
                    title={assigned?.template?.name ?? "Sin asignar"}
                    className="flex aspect-square flex-col items-center justify-center rounded-[var(--radius-fluent)] border border-line text-xs transition-colors hover:border-secondary disabled:cursor-not-allowed disabled:opacity-60"
                    style={color ? { background: color, color: "#fff" } : undefined}
                  >
                    <span className="font-medium">{day}</span>
                    {assigned?.template && <span className="w-full truncate px-0.5 text-[9px] leading-tight">{assigned.template.name}</span>}
                  </button>
                );
              })}
            </div>
            <p className="mt-2 text-xs text-ink-soft">Haz clic en un día para asignarle la plantilla seleccionada.</p>
          </div>

          {/* Asignar rango */}
          <Field label="Asignar un rango de fechas">
            <div className="flex flex-wrap items-end gap-2">
              <input type="date" value={rangeFrom} onChange={(e) => setRangeFrom(e.target.value)} className="rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary" />
              <span className="pb-2 text-sm text-ink-soft">→</span>
              <input type="date" value={rangeTo} onChange={(e) => setRangeTo(e.target.value)} className="rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary" />
              <Button variant="secondary" onClick={assignRange} disabled={busy || !templateId || !rangeFrom || !rangeTo}>Asignar rango</Button>
            </div>
          </Field>

          {/* Llenado rápido */}
          <div className="rounded-[var(--radius-fluent)] border border-line bg-canvas/60 p-4">
            <h4 className="mb-3 text-sm font-semibold text-primary">Llenado rápido (todo el año)</h4>
            <div className="mb-3 flex flex-wrap items-center gap-2">
              <span className="text-sm text-ink-soft">Días:</span>
              {WEEKDAYS.map(([w, l]) => (
                <button
                  key={w}
                  type="button"
                  onClick={() => toggleWeekday(w)}
                  className={`h-8 w-8 rounded-full text-xs font-medium transition-colors ${weekdays.includes(w) ? "bg-primary text-white" : "bg-line text-ink-soft hover:bg-line/70"}`}
                >
                  {l}
                </button>
              ))}
            </div>
            <div className="mb-3">
              <Toggle on={includeHolidays} onClick={() => setIncludeHolidays((v) => !v)} label="Incluir festivos" />
            </div>
            <Button variant="secondary" onClick={fillQuick} disabled={busy || !templateId || weekdays.length === 0}>Rellenar año</Button>
          </div>

          {/* Acciones */}
          <div className="flex items-center justify-between border-t border-line pt-4">
            <Button variant="ghost" onClick={clearAll} disabled={busy} className="text-red-600">Limpiar calendario</Button>
            <Button onClick={onClose}>Cerrar</Button>
          </div>
        </div>
      )}
    </Modal>
  );
}
