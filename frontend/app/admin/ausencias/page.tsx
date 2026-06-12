"use client";

import { useCallback, useEffect, useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { api, ApiError } from "@/lib/api";
import { Badge, Button, Card, Modal, PageHeader, Pagination, type Paginated, SelectField, Skeleton, Spinner, StatCard, TextField } from "@/components/ui";

type Employee = { id: string; full_name: string };
type LeaveRequest = {
  id: string;
  date_start: string;
  date_end: string;
  total_days: number | null;
  total_hours: number | null;
  status: string;
  description: string | null;
  employee?: { id: string; name: string };
};
type Vacations = { year: number; available: number; requested: number; approved: number; remaining: number };

const TABS = [["pendientes", "Pendientes"], ["listado", "Solicitudes"], ["vacaciones", "Vacaciones"]] as const;
const STATUS_TONES: Record<string, "ok" | "warn" | "neutral"> = { aprobada: "ok", pendiente: "warn", rechazada: "neutral" };

export default function AusenciasPage() {
  const [tab, setTab] = useState<(typeof TABS)[number][0]>("pendientes");

  return (
    <div>
      <PageHeader title="Ausencias" subtitle="Aprobaciones, solicitudes y vacaciones" />
      <div className="mb-6 flex flex-wrap gap-1 border-b border-line">
        {TABS.map(([key, label]) => (
          <button key={key} onClick={() => setTab(key)}
            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${tab === key ? "border-secondary text-primary" : "border-transparent text-ink-soft hover:text-ink"}`}>
            {label}
          </button>
        ))}
      </div>
      {tab === "pendientes" && <Pendientes />}
      {tab === "listado" && <Listado />}
      {tab === "vacaciones" && <VacacionesPanel />}
    </div>
  );
}

function rangeLabel(r: LeaveRequest) {
  const days = r.total_days ? `${r.total_days} d` : r.total_hours ? `${r.total_hours} h` : "—";
  return r.date_start === r.date_end ? `${r.date_start} · ${days}` : `${r.date_start} → ${r.date_end} · ${days}`;
}

function Pendientes() {
  const [rejecting, setRejecting] = useState<LeaveRequest | null>(null);
  const queryClient = useQueryClient();

  const query = useQuery({
    queryKey: ["leave-requests", "pending"],
    queryFn: () => api<{ data: LeaveRequest[] }>("/leave-requests/pending"),
  });
  const rows = query.data?.data ?? null;

  const refresh = useCallback(() => { void queryClient.invalidateQueries({ queryKey: ["leave-requests"] }); }, [queryClient]);

  async function approve(id: string) { await api(`/leave-requests/${id}/approve`, { method: "POST" }); refresh(); }

  if (query.isLoading || !rows) return <Spinner />;
  if (rows.length === 0) return <Card className="p-8 text-center"><p className="text-sm text-ink-soft">No hay solicitudes pendientes. 🎉</p></Card>;

  return (
    <div className="space-y-3">
      {rows.map((r) => (
        <Card key={r.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
          <div>
            <p className="font-medium text-ink">{r.employee?.name ?? "—"}</p>
            <p className="text-sm text-ink-soft">{rangeLabel(r)}{r.description ? ` · ${r.description}` : ""}</p>
          </div>
          <div className="flex gap-2">
            <Button variant="secondary" onClick={() => approve(r.id)}>Aprobar</Button>
            <Button variant="ghost" onClick={() => setRejecting(r)}>Rechazar</Button>
          </div>
        </Card>
      ))}
      {rejecting && <RejectModal request={rejecting} onClose={() => setRejecting(null)} onDone={() => { setRejecting(null); refresh(); }} />}
    </div>
  );
}

function RejectModal({ request, onClose, onDone }: { request: LeaveRequest; onClose: () => void; onDone: () => void }) {
  const [note, setNote] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function submit() {
    setError(null); setBusy(true);
    try {
      await api(`/leave-requests/${request.id}/reject`, { method: "POST", body: { note: note || null } });
      onDone();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo rechazar"); } finally { setBusy(false); }
  }

  return (
    <Modal title={`Rechazar solicitud · ${request.employee?.name ?? ""}`} onClose={onClose}>
      <TextField label="Motivo (opcional)" value={note} onChange={setNote} />
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy}>{busy ? "Rechazando…" : "Rechazar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}

function Listado() {
  const month = new Date().toISOString().slice(0, 7);
  const [from, setFrom] = useState(`${month}-01`);
  const [to, setTo] = useState(`${month}-31`);
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);

  const query = useQuery({
    queryKey: ["leave-requests", "list", from, to, status, page],
    queryFn: () => {
      const params = new URLSearchParams({ date_from: from, date_to: to, page: String(page) });
      if (status) params.set("status", status);
      return api<Paginated<LeaveRequest>>(`/leave-requests?${params}`);
    },
  });
  const rows = query.data?.data ?? [];

  return (
    <div className="space-y-4">
      <Card className="p-5">
        <div className="flex flex-wrap items-end gap-3">
          <TextField label="Desde" type="date" value={from} onChange={(v) => { setFrom(v); setPage(1); }} />
          <TextField label="Hasta" type="date" value={to} onChange={(v) => { setTo(v); setPage(1); }} />
          <SelectField label="Estado" value={status} onChange={(v) => { setStatus(v); setPage(1); }}
            options={[["", "Todos"], ["pendiente", "Pendientes"], ["aprobada", "Aprobadas"], ["rechazada", "Rechazadas"]]} />
        </div>
      </Card>
      <Card className="overflow-hidden">
        {query.isLoading ? <Skeleton /> : rows.length === 0 ? <p className="p-6 text-sm text-ink-soft">Sin solicitudes en el rango.</p> : (
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr><th className="px-5 py-3 font-medium">Empleado</th><th className="px-5 py-3 font-medium">Periodo</th><th className="px-5 py-3 font-medium">Estado</th></tr>
            </thead>
            <tbody className="divide-y divide-line">
              {rows.map((r) => (
                <tr key={r.id}>
                  <td className="px-5 py-3 text-ink">{r.employee?.name ?? "—"}</td>
                  <td className="px-5 py-3 text-ink-soft">{rangeLabel(r)}</td>
                  <td className="px-5 py-3"><Badge tone={STATUS_TONES[r.status] ?? "neutral"}>{r.status}</Badge></td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
      <Pagination meta={query.data?.meta} onPage={setPage} />
    </div>
  );
}

function VacacionesPanel() {
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [employeeId, setEmployeeId] = useState("");
  const [vac, setVac] = useState<Vacations | null>(null);

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: Employee[] }>("/employees?active=1");
      setEmployees(res.data);
      if (res.data[0]) setEmployeeId(res.data[0].id);
    })();
  }, []);

  const load = useCallback(async () => {
    if (!employeeId) return;
    const res = await api<{ data: Vacations }>(`/employees/${employeeId}/vacations`);
    setVac(res.data);
  }, [employeeId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  if (employees.length === 0) return <Card className="p-8 text-center"><p className="text-sm text-ink-soft">No hay empleados.</p></Card>;

  return (
    <div className="space-y-4">
      <Card className="p-5">
        <SelectField label="Empleado" value={employeeId} onChange={setEmployeeId} className="max-w-sm" options={employees.map((e) => [e.id, e.full_name] as const)} />
      </Card>
      {vac && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard label="Disponibles" value={vac.available} hint={`Ejercicio ${vac.year}`} />
          <StatCard label="Solicitadas" value={vac.requested} />
          <StatCard label="Aprobadas" value={vac.approved} />
          <StatCard label="Restantes" value={vac.remaining} />
        </div>
      )}
    </div>
  );
}
