"use client";

import { useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { Badge, Button, Card, PageHeader, Spinner } from "@/components/ui";
import { DateInput } from "@/components/DateInput";

type LeaveType = { id: string; name: string; type: string; count_in: string };
type LeaveRow = { id: string; status: string; date_start: string; date_end: string; total_days: number | null };

const STATUS_TONE: Record<string, "ok" | "warn" | "info" | "neutral"> = {
  aprobada: "ok",
  pendiente: "warn",
  rechazada: "neutral",
  cancelada: "neutral",
};

export default function AusenciasPage() {
  const [types, setTypes] = useState<LeaveType[]>([]);
  const [rows, setRows] = useState<LeaveRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ leave_type_id: "", date_start: "", date_end: "" });
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function reload() {
    const [t, l] = await Promise.all([
      api<{ data: LeaveType[] }>("/me/leave-types"),
      api<{ data: LeaveRow[] }>("/me/leave-requests"),
    ]);
    setTypes(t.data);
    setRows(l.data);
  }

  useEffect(() => {
    void (async () => {
      try {
        await reload();
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await api("/me/leave-requests", { method: "POST", body: form });
      setForm({ leave_type_id: "", date_start: "", date_end: "" });
      await reload();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "No se pudo enviar la solicitud");
    } finally {
      setBusy(false);
    }
  }

  if (loading) return <Spinner />;

  return (
    <div>
      <PageHeader title="Ausencias" subtitle="Solicita y consulta tus ausencias" />

      <div className="grid gap-6 lg:grid-cols-[360px_1fr]">
        <Card className="p-6">
          <h2 className="mb-4 text-sm font-semibold text-primary">Nueva solicitud</h2>
          <form onSubmit={onSubmit} className="space-y-4">
            <label className="block">
              <span className="mb-1.5 block text-sm font-medium text-ink">Tipo</span>
              <select
                required
                value={form.leave_type_id}
                onChange={(e) => setForm({ ...form, leave_type_id: e.target.value })}
                className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
              >
                <option value="" disabled>
                  Selecciona…
                </option>
                {types.map((t) => (
                  <option key={t.id} value={t.id}>
                    {t.name}
                  </option>
                ))}
              </select>
            </label>
            <DateInput label="Desde" minYear={2020} value={form.date_start} onChange={(v) => setForm({ ...form, date_start: v })} />
            <DateInput label="Hasta" minYear={2020} value={form.date_end} onChange={(v) => setForm({ ...form, date_end: v })} />

            {error && <p className="rounded-[var(--radius-fluent)] bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
            {types.length === 0 && (
              <p className="text-xs text-ink-soft">No hay tipos de ausencia configurados en tu convenio.</p>
            )}

            <Button type="submit" disabled={busy} className="w-full">
              {busy ? "Enviando…" : "Solicitar"}
            </Button>
          </form>
        </Card>

        <Card className="overflow-hidden">
          {rows.length === 0 ? (
            <p className="p-6 text-sm text-ink-soft">Aún no tienes solicitudes.</p>
          ) : (
            <table className="w-full text-sm">
              <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
                <tr>
                  <th className="px-5 py-3 font-medium">Periodo</th>
                  <th className="px-5 py-3 font-medium">Días</th>
                  <th className="px-5 py-3 font-medium">Estado</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {rows.map((r) => (
                  <tr key={r.id}>
                    <td className="px-5 py-3">
                      {r.date_start} → {r.date_end}
                    </td>
                    <td className="px-5 py-3 text-ink-soft">{r.total_days ?? "—"}</td>
                    <td className="px-5 py-3">
                      <Badge tone={STATUS_TONE[r.status] ?? "neutral"}>{r.status}</Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </Card>
      </div>
    </div>
  );
}
