"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import { useDebounce } from "@/lib/hooks";
import { Badge, Button, Card, EmptyState, PageHeader, SelectField, Skeleton, TextField } from "@/components/ui";

type Tenant = {
  id: number;
  name: string;
  subdomain: string;
  status: string;
  plan: { id: number; name: string; slug: string } | null;
  plan_id: number | null;
  trial_days_left: number | null;
  employees_count: number | null;
  members_count: number | null;
  created_at: string | null;
};
type Plan = { id: number; slug: string; name: string };

const STATUS_TONES: Record<string, "ok" | "warn" | "neutral"> = { active: "ok", trial: "warn", suspended: "neutral", cancelled: "neutral" };
const fmtDate = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString("es-ES") : "—");

export default function TenantsPage() {
  const router = useRouter();
  const [tenants, setTenants] = useState<Tenant[] | null>(null);
  const [plans, setPlans] = useState<Plan[]>([]);
  const [status, setStatus] = useState("");
  const [plan, setPlan] = useState("");
  const [search, setSearch] = useState("");
  const [error, setError] = useState<string | null>(null);
  const debouncedSearch = useDebounce(search, 300);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Plan[] }>("/superadmin/plans");
        setPlans(res.data);
      } catch {
        // Los filtros de plan se quedan vacíos; no es crítico.
      }
    })();
  }, []);

  const load = useCallback(async () => {
    setError(null);
    setTenants(null);
    try {
      const params = new URLSearchParams();
      if (status) params.set("status", status);
      if (plan) params.set("plan", plan);
      if (debouncedSearch) params.set("search", debouncedSearch);
      const res = await api<{ data: Tenant[] }>(`/superadmin/tenants?${params}`);
      setTenants(res.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "No se pudieron cargar los tenants.");
    }
  }, [status, plan, debouncedSearch]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function setStatusOf(t: Tenant, value: string) {
    await api(`/superadmin/tenants/${t.id}`, { method: "PUT", body: { status: value } });
    await load();
  }
  async function setPlanOf(t: Tenant, planId: string) {
    await api(`/superadmin/tenants/${t.id}`, { method: "PUT", body: { plan_id: planId ? Number(planId) : null } });
    await load();
  }
  async function impersonate(t: Tenant) {
    const res = await api<{ data: { magic_link: string | null } }>(`/superadmin/tenants/${t.id}/impersonate`, { method: "POST" });
    if (res.data.magic_link) window.open(res.data.magic_link, "_blank");
  }
  async function remove(t: Tenant) {
    if (!window.confirm(`¿Eliminar el tenant "${t.name}"? Esta acción borra su schema y es irreversible.`)) return;
    await api(`/superadmin/tenants/${t.id}`, { method: "DELETE" });
    await load();
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Tenants" subtitle="Clientes de la plataforma"
        action={<Link href="/superadmin/tenants/nuevo"><Button>Nuevo tenant</Button></Link>} />

      <Card className="p-5">
        <div className="flex flex-wrap items-end gap-3">
          <SelectField label="Estado" value={status} onChange={setStatus} options={[["", "Todos"], ["active", "Activos"], ["trial", "Trial"], ["suspended", "Suspendidos"], ["cancelled", "Cancelados"]]} />
          <SelectField label="Plan" value={plan} onChange={setPlan} options={[["", "Todos"], ...plans.map((p) => [p.slug, p.name] as const)]} />
          <TextField label="Buscar" value={search} onChange={setSearch} placeholder="Nombre o subdominio" className="flex-1" />
        </div>
      </Card>

      {error ? (
        <EmptyState title="No se pudieron cargar los tenants" message={error} action={<Button onClick={() => void load()}>Reintentar</Button>} />
      ) : (
      <Card className="overflow-hidden">
        {!tenants ? <Skeleton /> : tenants.length === 0 ? <p className="p-6 text-sm text-ink-soft">Sin tenants.</p> : (
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr>
                <th className="px-4 py-3 font-medium">Tenant</th>
                <th className="px-4 py-3 font-medium">Plan</th>
                <th className="px-4 py-3 font-medium">Estado</th>
                <th className="px-4 py-3 font-medium">Emp / Soc</th>
                <th className="px-4 py-3 font-medium">Trial</th>
                <th className="px-4 py-3 font-medium">Alta</th>
                <th className="px-4 py-3 font-medium"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {tenants.map((t) => (
                <tr key={t.id} className="hover:bg-canvas">
                  <td className="px-4 py-3">
                    <button onClick={() => router.push(`/superadmin/tenants/${t.id}`)} className="text-left">
                      <p className="font-medium text-primary hover:underline">{t.name}</p>
                      <p className="text-xs text-ink-soft">{t.subdomain}.gestioname.app</p>
                    </button>
                  </td>
                  <td className="px-4 py-3">
                    <select value={String(t.plan_id ?? "")} onChange={(e) => setPlanOf(t, e.target.value)}
                      className="rounded border border-line bg-canvas px-2 py-1 text-xs outline-none focus:border-secondary">
                      <option value="">—</option>
                      {plans.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                    </select>
                  </td>
                  <td className="px-4 py-3"><Badge tone={STATUS_TONES[t.status] ?? "neutral"}>{t.status}</Badge></td>
                  <td className="px-4 py-3 text-ink-soft">{t.employees_count ?? 0} / {t.members_count ?? 0}</td>
                  <td className="px-4 py-3 text-ink-soft">{t.trial_days_left != null ? `${t.trial_days_left} d` : "—"}</td>
                  <td className="px-4 py-3 text-ink-soft">{fmtDate(t.created_at)}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap justify-end gap-2 text-xs">
                      {t.status === "active"
                        ? <button onClick={() => setStatusOf(t, "suspended")} className="text-amber-700 hover:underline">Suspender</button>
                        : <button onClick={() => setStatusOf(t, "active")} className="text-[#0d6b50] hover:underline">Activar</button>}
                      <button onClick={() => impersonate(t)} className="text-primary hover:underline">Impersonar</button>
                      <button onClick={() => remove(t)} className="text-red-600 hover:underline">Borrar</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>
      )}
    </div>
  );
}
