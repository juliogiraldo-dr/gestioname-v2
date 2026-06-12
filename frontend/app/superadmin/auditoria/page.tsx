"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { Badge, Button, Card, EmptyState, PageHeader, Skeleton } from "@/components/ui";

type Audit = {
  id: number; action: string; actor: string | null; tenant: string | null;
  details: Record<string, unknown>; ip: string | null; created_at: string;
};
type Meta = { current_page: number; last_page: number; total: number };

const summary = (d: Record<string, unknown>) =>
  Object.entries(d).filter(([k]) => k !== "actor_email").map(([k, v]) => `${k}: ${v}`).join(" · ");

export default function AuditoriaPage() {
  const [rows, setRows] = useState<Audit[] | null>(null);
  const [meta, setMeta] = useState<Meta | null>(null);
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    setRows(null);
    try {
      const res = await api<{ data: Audit[]; meta: Meta }>(`/superadmin/audit?page=${page}`);
      setRows(res.data);
      setMeta(res.meta);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "No se pudo cargar la auditoría.");
    }
  }, [page]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  return (
    <div className="space-y-6">
      <PageHeader title="Auditoría" subtitle="Acciones del super-admin" />

      {error && (
        <EmptyState title="No se pudo cargar la auditoría" message={error} action={<Button onClick={() => void load()}>Reintentar</Button>} />
      )}

      <Card className="overflow-hidden">
        {error ? null : !rows ? <Skeleton rows={8} /> : rows.length === 0 ? <p className="p-6 text-sm text-ink-soft">Sin registros.</p> : (
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr><th className="px-5 py-3 font-medium">Acción</th><th className="px-5 py-3 font-medium">Operador</th><th className="px-5 py-3 font-medium">Tenant</th><th className="px-5 py-3 font-medium">Detalle</th><th className="px-5 py-3 font-medium">Fecha</th></tr>
            </thead>
            <tbody className="divide-y divide-line">
              {rows.map((a) => (
                <tr key={a.id}>
                  <td className="px-5 py-3"><Badge tone="info">{a.action}</Badge></td>
                  <td className="px-5 py-3 text-ink-soft">{a.actor ?? "—"}</td>
                  <td className="px-5 py-3 text-ink-soft">{a.tenant ?? "—"}</td>
                  <td className="px-5 py-3 text-xs text-ink-soft">{summary(a.details)}</td>
                  <td className="px-5 py-3 text-xs text-ink-soft">{new Date(a.created_at).toLocaleString("es-ES")}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>

      {meta && meta.last_page > 1 && (
        <div className="flex items-center justify-center gap-3">
          <Button variant="ghost" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1}>Anterior</Button>
          <span className="text-sm text-ink-soft">Página {meta.current_page} de {meta.last_page} · {meta.total} acciones</span>
          <Button variant="ghost" onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))} disabled={page >= meta.last_page}>Siguiente</Button>
        </div>
      )}
    </div>
  );
}
