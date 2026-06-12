"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { api, ApiError } from "@/lib/api";
import { useActiveCompany } from "@/lib/company";
import { formatTime } from "@/lib/utils";
import { Badge, Button, Card, Modal, PageHeader, SelectField, Spinner, TextField } from "@/components/ui";

type WorkCenter = { id: string; name: string };
type Attendance = {
  id: string;
  clocked_at: string;
  method: string;
  work_mode?: string | null;
  employee?: { id: string; name: string };
  milestone?: { name: string; type: string };
};

const time = (iso: string) => formatTime(iso);
const MODE_LABEL: Record<string, string> = { presencial: "Oficina", teletrabajo: "Teletrabajo" };

export default function FichajesPage() {
  const today = new Date().toISOString().slice(0, 10);
  const company = useActiveCompany();
  const companyId = company?.activeId ?? "";
  const [centers, setCenters] = useState<WorkCenter[]>([]);
  const [centerId, setCenterId] = useState("");
  const [date, setDate] = useState(today);
  const [employees, setEmployees] = useState<{ id: string }[]>([]);
  const [correcting, setCorrecting] = useState<Attendance | null>(null);
  const [deleting, setDeleting] = useState<Attendance | null>(null);
  const queryClient = useQueryClient();

  useEffect(() => {
    if (!companyId) return;
    void (async () => {
      const res = await api<{ data: WorkCenter[] }>(`/companies/${companyId}/work-centers`);
      setCenters(res.data);
      setCenterId("");
    })();
  }, [companyId]);

  const query = useQuery({
    queryKey: ["fichajes", date, companyId, centerId],
    enabled: !!companyId,
    queryFn: () => {
      const body: Record<string, string> = { date, format: "json", company_id: companyId };
      if (centerId) body.work_center_id = centerId;
      return api<{ data: Attendance[] }>("/reports/daily-attendance", { method: "POST", body });
    },
  });
  const rows = useMemo(() => query.data?.data ?? [], [query.data]);
  const loading = query.isLoading;
  const refresh = useCallback(() => { void queryClient.invalidateQueries({ queryKey: ["fichajes"] }); }, [queryClient]);

  useEffect(() => {
    if (!companyId) return;
    void (async () => {
      const res = await api<{ data: { id: string }[] }>(`/employees?company_id=${companyId}&active=1`);
      setEmployees(res.data);
    })();
  }, [companyId]);

  const alerts = useMemo(() => {
    const counts = new Map<string, { in: number; out: number }>();
    const present = new Set<string>();
    for (const r of rows) {
      const id = r.employee?.id ?? "";
      present.add(id);
      const c = counts.get(id) ?? { in: 0, out: 0 };
      if (r.milestone?.type === "entrada") c.in++;
      else if (r.milestone?.type === "salida") c.out++;
      counts.set(id, c);
    }
    const incompletos = [...counts.values()].filter((c) => c.in > c.out).length;
    const sinFichar = employees.filter((e) => !present.has(e.id)).length;
    return { incompletos, sinFichar };
  }, [rows, employees]);

  return (
    <div className="space-y-6">
      <PageHeader title="Fichajes" subtitle="Vista diaria, correcciones (ET 34.9) y auditoría" />

      <div className="flex flex-wrap gap-3">
        <Badge tone={alerts.sinFichar > 0 ? "warn" : "ok"}>{alerts.sinFichar} sin fichar hoy</Badge>
        <Badge tone={alerts.incompletos > 0 ? "warn" : "ok"}>{alerts.incompletos} fichajes incompletos</Badge>
      </div>

      <Card className="p-5">
        <div className="flex flex-wrap items-end gap-3">
          <SelectField label="Centro" value={centerId} onChange={setCenterId} options={[["", "Todos"], ...centers.map((c) => [c.id, c.name] as const)]} />
          <TextField label="Fecha" type="date" value={date} onChange={setDate} />
        </div>
      </Card>

      {loading ? <Spinner /> : (
        <>
          <Card className="p-5">
            <h2 className="mb-4 text-sm font-semibold text-primary">Jornada visual</h2>
            <Timeline rows={rows} />
          </Card>

          <Card className="overflow-hidden">
            <div className="border-b border-line px-5 py-3"><h2 className="text-sm font-semibold text-primary">Fichajes del día</h2></div>
            {rows.length === 0 ? <p className="p-6 text-sm text-ink-soft">Sin fichajes en la fecha.</p> : (
              <table className="w-full text-sm">
                <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
                  <tr><th className="px-5 py-3 font-medium">Empleado</th><th className="px-5 py-3 font-medium">Hito</th><th className="px-5 py-3 font-medium">Hora</th><th className="px-5 py-3 font-medium">Modalidad</th><th className="px-5 py-3 font-medium">Método</th><th className="px-5 py-3"></th></tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {rows.map((r) => (
                    <tr key={r.id}>
                      <td className="px-5 py-3 text-ink">{r.employee?.name ?? "—"}</td>
                      <td className="px-5 py-3"><Badge tone={r.milestone?.type === "entrada" ? "ok" : "info"}>{r.milestone?.name ?? "—"}</Badge></td>
                      <td className="px-5 py-3 font-medium text-ink">{time(r.clocked_at)}</td>
                      <td className="px-5 py-3">{r.work_mode ? <Badge tone={r.work_mode === "teletrabajo" ? "info" : "neutral"}>{MODE_LABEL[r.work_mode] ?? r.work_mode}</Badge> : <span className="text-ink-soft">—</span>}</td>
                      <td className="px-5 py-3 text-ink-soft">{r.method}</td>
                      <td className="px-5 py-3 text-right">
                        <button onClick={() => setCorrecting(r)} className="mr-3 text-xs font-medium text-primary hover:underline">Corregir</button>
                        <button onClick={() => setDeleting(r)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </Card>
        </>
      )}

      {correcting && <CorrectModal attendance={correcting} onClose={() => setCorrecting(null)} onDone={() => { setCorrecting(null); refresh(); }} />}
      {deleting && <DeleteModal attendance={deleting} onClose={() => setDeleting(null)} onDone={() => { setDeleting(null); refresh(); }} />}
    </div>
  );
}

/** Barra de jornada por empleado (06:00–22:00). */
function Timeline({ rows }: { rows: Attendance[] }) {
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

  if (rows.length === 0) return <p className="text-sm text-ink-soft">Sin fichajes para mostrar.</p>;

  return (
    <div className="space-y-3">
      <div className="flex justify-between px-40 text-xs text-ink-soft"><span>06:00</span><span>14:00</span><span>22:00</span></div>
      {byEmployee.map((emp) => {
        const segs = pairs(emp.times);
        const worked = segs.reduce((a, s) => a + (s.end - s.start), 0);
        return (
          <div key={emp.name} className="flex items-center gap-3">
            <div className="w-36 shrink-0 truncate text-sm text-ink">{emp.name}</div>
            <div className="relative h-6 flex-1 rounded bg-canvas">
              {segs.map((s, i) => {
                const left = Math.max(0, ((s.start - WIN_START) / WIN_SPAN) * 100);
                const width = Math.max(1, ((s.end - s.start) / WIN_SPAN) * 100);
                return <div key={i} className="absolute top-0 h-6 rounded bg-secondary" style={{ left: `${left}%`, width: `${Math.min(width, 100 - left)}%` }} title={`${fmt(s.start)}–${fmt(s.end)}`} />;
              })}
            </div>
            <div className="w-16 shrink-0 text-right"><Badge tone="info">{(worked / 60).toFixed(1)} h</Badge></div>
          </div>
        );
      })}
    </div>
  );
}

function pairs(times: { min: number; type: string }[]) {
  const sorted = [...times].sort((a, b) => a.min - b.min);
  const out: { start: number; end: number }[] = [];
  let open: number | null = null;
  for (const t of sorted) {
    if (t.type === "entrada") open = t.min;
    else if (t.type === "salida" && open !== null) { out.push({ start: open, end: t.min }); open = null; }
  }
  return out;
}
const fmt = (min: number) => `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;

function CorrectModal({ attendance, onClose, onDone }: { attendance: Attendance; onClose: () => void; onDone: () => void }) {
  const d = new Date(attendance.clocked_at);
  const [hhmm, setHhmm] = useState(`${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`);
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit() {
    setError(null); setBusy(true);
    try {
      const date = attendance.clocked_at.slice(0, 10);
      await api(`/attendance/${attendance.id}`, { method: "PUT", body: { new_clocked_at: `${date}T${hhmm}:00`, reason } });
      onDone();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo corregir"); } finally { setBusy(false); }
  }

  return (
    <Modal title={`Corregir fichaje · ${attendance.employee?.name ?? ""}`} onClose={onClose}>
      <p className="mb-4 text-sm text-ink-soft">Hito {attendance.milestone?.name} · hora actual {time(attendance.clocked_at)}. La corrección queda registrada en auditoría (ET 34.9).</p>
      <div className="grid gap-3 sm:grid-cols-2">
        <TextField label="Nueva hora" type="time" value={hhmm} onChange={setHhmm} />
        <TextField label="Motivo (obligatorio)" value={reason} onChange={setReason} />
      </div>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !reason}>{busy ? "Guardando…" : "Guardar corrección"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}

function DeleteModal({ attendance, onClose, onDone }: { attendance: Attendance; onClose: () => void; onDone: () => void }) {
  const [reason, setReason] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit() {
    setError(null); setBusy(true);
    try {
      await api(`/attendance/${attendance.id}`, { method: "DELETE", body: { reason } });
      onDone();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo eliminar"); } finally { setBusy(false); }
  }

  return (
    <Modal title="Eliminar fichaje" onClose={onClose}>
      <p className="mb-4 text-sm text-ink-soft">El borrado es lógico y queda registrado en auditoría. Indica el motivo.</p>
      <TextField label="Motivo (obligatorio)" value={reason} onChange={setReason} />
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !reason}>{busy ? "Eliminando…" : "Eliminar fichaje"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}
