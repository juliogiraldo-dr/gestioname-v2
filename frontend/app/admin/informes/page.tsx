"use client";

import { useEffect, useMemo, useState } from "react";
import { api, ApiError, downloadFile } from "@/lib/api";
import { Badge, Button, Card, PageHeader, Spinner } from "@/components/ui";

type Company = { id: string; name: string };

type DailyRow = {
  id: string;
  clocked_at: string;
  employee?: { id: string; name: string };
  milestone?: { type: string };
};

const WTR_OPTIONS = [
  { key: "include_work_center", label: "Incluir centro de trabajo" },
  { key: "include_delays", label: "Incluir retrasos" },
  { key: "include_geolocation", label: "Incluir geolocalización" },
  { key: "include_method", label: "Incluir método de fichaje" },
  { key: "decimal_format", label: "Formato decimal (en vez de HH:MM)" },
  { key: "split_by_employee", label: "Un fichero por empleado (ZIP)" },
] as const;

export default function InformesPage() {
  const today = new Date().toISOString().slice(0, 10);
  const monthStart = today.slice(0, 8) + "01";

  const [companies, setCompanies] = useState<Company[]>([]);
  const [companyId, setCompanyId] = useState("");

  // Registro horario (ET 34.9)
  const [dateFrom, setDateFrom] = useState(monthStart);
  const [dateTo, setDateTo] = useState(today);
  const [format, setFormat] = useState<"excel" | "pdf">("excel");
  const [options, setOptions] = useState<Record<string, boolean>>({
    include_work_center: true,
    include_delays: true,
  });
  const [password, setPassword] = useState("");
  const [wtrBusy, setWtrBusy] = useState(false);
  const [wtrError, setWtrError] = useState<string | null>(null);

  // Resumen de ausencias
  const [year, setYear] = useState(new Date().getFullYear());
  const [leaveBusy, setLeaveBusy] = useState(false);

  // Informe diario
  const [dailyDate, setDailyDate] = useState(today);
  const [daily, setDaily] = useState<DailyRow[]>([]);
  const [dailyLoading, setDailyLoading] = useState(false);

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: Company[] }>("/companies");
      setCompanies(res.data);
      if (res.data[0]) setCompanyId(res.data[0].id);
    })();
  }, []);

  const filterBody = useMemo(
    () => (companyId ? { company_id: companyId } : {}),
    [companyId],
  );

  async function generateWtr() {
    setWtrError(null);
    setWtrBusy(true);
    try {
      await downloadFile("/reports/work-time-record", {
        body: {
          ...filterBody,
          date_from: dateFrom,
          date_to: dateTo,
          format,
          options: { ...options, password: password || undefined },
        },
        fallbackName: format === "pdf" ? "registro-horario.pdf" : "registro-horario.xlsx",
      });
    } catch (err) {
      setWtrError(err instanceof ApiError ? err.message : "No se pudo generar el informe");
    } finally {
      setWtrBusy(false);
    }
  }

  async function generateLeave() {
    setLeaveBusy(true);
    try {
      await downloadFile("/reports/leave-summary", {
        body: { ...filterBody, year },
        fallbackName: `resumen-ausencias-${year}.xlsx`,
      });
    } finally {
      setLeaveBusy(false);
    }
  }

  async function loadDaily() {
    setDailyLoading(true);
    try {
      const res = await api<{ data: DailyRow[] }>("/reports/daily-attendance", {
        method: "POST",
        body: { ...filterBody, date: dailyDate, format: "json" },
      });
      setDaily(res.data);
    } finally {
      setDailyLoading(false);
    }
  }

  async function downloadDailyPdf() {
    await downloadFile("/reports/daily-attendance", {
      body: { ...filterBody, date: dailyDate, format: "pdf" },
      fallbackName: `informe-diario-${dailyDate}.pdf`,
    });
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Informes" subtitle="Registro de jornada (ET 34.9), informe diario y resumen de ausencias" />

      <Card className="p-5">
        <label className="block max-w-sm">
          <span className="mb-1.5 block text-sm font-medium text-ink">Empresa</span>
          <select
            value={companyId}
            onChange={(e) => setCompanyId(e.target.value)}
            className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
          >
            <option value="">Todas las empresas</option>
            {companies.map((c) => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
        </label>
      </Card>

      {/* Registro horario ET 34.9 */}
      <Card className="p-5">
        <h2 className="mb-1 text-lg font-semibold text-primary">Registro de jornada (ET 34.9)</h2>
        <p className="mb-4 text-sm text-ink-soft">Horas previstas, realizadas, sobretiempo y retrasos por empleado.</p>

        <div className="grid gap-4 sm:grid-cols-3">
          <Field label="Desde">
            <DateInput value={dateFrom} onChange={setDateFrom} />
          </Field>
          <Field label="Hasta">
            <DateInput value={dateTo} onChange={setDateTo} />
          </Field>
          <Field label="Formato">
            <select
              value={format}
              onChange={(e) => setFormat(e.target.value as "excel" | "pdf")}
              className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
            >
              <option value="excel">Excel (.xlsx)</option>
              <option value="pdf">PDF</option>
            </select>
          </Field>
        </div>

        <div className="mt-4 grid gap-2 sm:grid-cols-2">
          {WTR_OPTIONS.map((o) => (
            <label key={o.key} className="flex items-center gap-2 text-sm text-ink">
              <input
                type="checkbox"
                checked={options[o.key] ?? false}
                onChange={(e) => setOptions((prev) => ({ ...prev, [o.key]: e.target.checked }))}
                className="h-4 w-4 rounded border-line text-primary focus:ring-secondary/40"
              />
              {o.label}
            </label>
          ))}
        </div>

        {format === "excel" && (
          <div className="mt-4 max-w-xs">
            <Field label="Contraseña del Excel (opcional)">
              <input
                type="text"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Sin contraseña"
                className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
              />
            </Field>
          </div>
        )}

        {wtrError && <p className="mt-4 rounded-[var(--radius-fluent)] bg-red-50 px-3 py-2 text-sm text-red-700">{wtrError}</p>}

        <div className="mt-5">
          <Button onClick={generateWtr} disabled={wtrBusy}>
            {wtrBusy ? "Generando…" : "Generar informe"}
          </Button>
        </div>
      </Card>

      {/* Informe diario con barra visual */}
      <Card className="p-5">
        <div className="mb-4 flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 className="mb-1 text-lg font-semibold text-primary">Informe diario</h2>
            <p className="text-sm text-ink-soft">Jornada visual por empleado.</p>
          </div>
          <div className="flex items-end gap-3">
            <Field label="Fecha">
              <DateInput value={dailyDate} onChange={setDailyDate} />
            </Field>
            <Button variant="secondary" onClick={loadDaily} disabled={dailyLoading}>Ver</Button>
            <Button variant="ghost" onClick={downloadDailyPdf}>PDF</Button>
          </div>
        </div>

        {dailyLoading ? <Spinner /> : <DailyTimeline rows={daily} />}
      </Card>

      {/* Resumen de ausencias */}
      <Card className="p-5">
        <h2 className="mb-1 text-lg font-semibold text-primary">Resumen de ausencias</h2>
        <p className="mb-4 text-sm text-ink-soft">Disponibles, solicitadas, aprobadas, rechazadas y en espera.</p>
        <div className="flex items-end gap-3">
          <Field label="Año">
            <input
              type="number"
              value={year}
              onChange={(e) => setYear(Number(e.target.value))}
              className="w-28 rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
            />
          </Field>
          <Button onClick={generateLeave} disabled={leaveBusy}>
            {leaveBusy ? "Generando…" : "Descargar Excel"}
          </Button>
        </div>
      </Card>
    </div>
  );
}

/** Barra de jornada por empleado en una ventana de 06:00 a 22:00. */
function DailyTimeline({ rows }: { rows: DailyRow[] }) {
  const WIN_START = 6 * 60;
  const WIN_SPAN = 16 * 60;

  const byEmployee = useMemo(() => {
    const map = new Map<string, { name: string; times: { min: number; type: string }[] }>();
    for (const r of rows) {
      const id = r.employee?.id ?? "—";
      const d = new Date(r.clocked_at);
      const min = d.getHours() * 60 + d.getMinutes();
      if (!map.has(id)) map.set(id, { name: r.employee?.name ?? "Sin asignar", times: [] });
      map.get(id)!.times.push({ min, type: r.milestone?.type ?? "" });
    }
    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
  }, [rows]);

  if (rows.length === 0) {
    return <p className="text-sm text-ink-soft">Pulsa «Ver» para cargar los fichajes del día seleccionado.</p>;
  }

  return (
    <div className="space-y-3">
      <div className="flex justify-between px-40 text-xs text-ink-soft">
        <span>06:00</span><span>14:00</span><span>22:00</span>
      </div>
      {byEmployee.map((emp) => {
        const segments = pairs(emp.times);
        const worked = segments.reduce((acc, s) => acc + (s.end - s.start), 0);
        return (
          <div key={emp.name} className="flex items-center gap-3">
            <div className="w-36 shrink-0 truncate text-sm text-ink">{emp.name}</div>
            <div className="relative h-6 flex-1 rounded bg-canvas">
              {segments.map((s, i) => {
                const left = Math.max(0, ((s.start - WIN_START) / WIN_SPAN) * 100);
                const width = Math.max(1, ((s.end - s.start) / WIN_SPAN) * 100);
                return (
                  <div
                    key={i}
                    className="absolute top-0 h-6 rounded bg-secondary"
                    style={{ left: `${left}%`, width: `${Math.min(width, 100 - left)}%` }}
                    title={`${fmt(s.start)} – ${fmt(s.end)}`}
                  />
                );
              })}
            </div>
            <div className="w-16 shrink-0 text-right">
              <Badge tone="info">{(worked / 60).toFixed(1)} h</Badge>
            </div>
          </div>
        );
      })}
    </div>
  );
}

function pairs(times: { min: number; type: string }[]): { start: number; end: number }[] {
  const sorted = [...times].sort((a, b) => a.min - b.min);
  const out: { start: number; end: number }[] = [];
  let open: number | null = null;
  for (const t of sorted) {
    if (t.type === "entrada") open = t.min;
    else if (t.type === "salida" && open !== null) {
      out.push({ start: open, end: t.min });
      open = null;
    }
  }
  return out;
}

function fmt(min: number): string {
  return `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1.5 block text-sm font-medium text-ink">{label}</span>
      {children}
    </label>
  );
}

function DateInput({ value, onChange }: { value: string; onChange: (v: string) => void }) {
  return (
    <input
      type="date"
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
    />
  );
}
