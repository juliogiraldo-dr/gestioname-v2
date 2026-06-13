"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import { formatDateTime } from "@/lib/utils";
import { Badge, Button, Card, EmptyState, PageHeader, SelectField, Spinner, TextField, Toggle } from "@/components/ui";

type Tenant = {
  id: number; name: string; subdomain: string; custom_domain: string | null; status: string;
  plan_id: number | null; plan: { name: string; slug: string } | null; trial_days_left: number | null;
};
type Plan = { id: number; slug: string; name: string };
type Override = { limits: Record<string, number | null> | null; modules_allowed: string[] | null };
type Module = { key: string; label: string; enabled: boolean };
type TUser = { id: string; name: string; email: string; roles: string[]; active: boolean; last_login_at: string | null };
type Audit = { id: number; action: string; actor: string | null; details: Record<string, unknown>; created_at: string };

const LIMIT_KEYS = ["companies", "employees", "entities", "members", "users"] as const;
const ROLES = ["admin", "gestoria", "rrhh-coordinator", "operator", "employee", "member"] as const;
const STATUS_TONES: Record<string, "ok" | "warn" | "neutral"> = { active: "ok", trial: "warn", suspended: "neutral", cancelled: "neutral" };
const fmtDateTime = (iso: string | null) => (iso ? formatDateTime(iso) : "Nunca");

export default function TenantDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [plans, setPlans] = useState<Plan[]>([]);
  const [override, setOverride] = useState<Override | null>(null);
  const [modules, setModules] = useState<Module[]>([]);
  const [users, setUsers] = useState<TUser[]>([]);
  const [audit, setAudit] = useState<Audit[]>([]);
  const [resetLinks, setResetLinks] = useState<Record<string, string>>({});
  const [msg, setMsg] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [t, p, o, m, u, a] = await Promise.all([
        api<{ data: Tenant }>(`/superadmin/tenants/${id}`),
        api<{ data: Plan[] }>("/superadmin/plans"),
        api<{ data: Override }>(`/superadmin/tenants/${id}/override`),
        api<{ data: Module[] }>(`/superadmin/tenants/${id}/modules`),
        api<{ data: TUser[] }>(`/superadmin/tenants/${id}/users`),
        api<{ data: Audit[] }>(`/superadmin/audit?tenant_id=${id}`),
      ]);
      setTenant(t.data); setPlans(p.data);
      setOverride({ limits: o.data.limits, modules_allowed: o.data.modules_allowed });
      setModules(m.data); setUsers(u.data); setAudit(a.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "No se pudo cargar el tenant.");
    }
  }, [id]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function patch(body: Record<string, unknown>) { await api(`/superadmin/tenants/${id}`, { method: "PUT", body }); setMsg("Guardado"); await load(); }
  async function saveOverride() { await api(`/superadmin/tenants/${id}/override`, { method: "PUT", body: override ?? {} }); setMsg("Override guardado"); await load(); }
  async function toggleModule(m: Module) {
    setModules((prev) => prev.map((x) => (x.key === m.key ? { ...x, enabled: !x.enabled } : x)));
    await api(`/superadmin/tenants/${id}/modules/${m.key}`, { method: "PATCH", body: { enabled: !m.enabled } });
  }
  async function impersonate() {
    const res = await api<{ data: { magic_link: string | null } }>(`/superadmin/tenants/${id}/impersonate`, { method: "POST" });
    if (res.data.magic_link) window.open(res.data.magic_link, "_blank");
    await load();
  }
  async function resetPassword(u: TUser) {
    const res = await api<{ data: { magic_link: string } }>(`/superadmin/tenants/${id}/users/${u.id}/reset-password`, { method: "POST" });
    setResetLinks((p) => ({ ...p, [u.id]: res.data.magic_link }));
    await load();
  }
  async function changeRole(u: TUser, role: string) { await api(`/superadmin/tenants/${id}/users/${u.id}/role`, { method: "PUT", body: { role } }); await load(); }
  async function toggleActive(u: TUser) { await api(`/superadmin/tenants/${id}/users/${u.id}/active`, { method: "PATCH" }); await load(); }

  if (error) {
    return (
      <div className="space-y-4">
        <Link href="/superadmin/tenants" className="text-sm text-primary hover:underline">← Tenants</Link>
        <EmptyState title="No se pudo cargar el tenant" message={error} action={<Button onClick={() => void load()}>Reintentar</Button>} />
      </div>
    );
  }
  if (!tenant || !override) return <Spinner />;

  const setLimit = (k: string, v: string) => setOverride((p) => ({ ...p!, limits: { ...(p!.limits ?? {}), [k]: v === "" ? null : Number(v) } }));

  return (
    <div className="space-y-6">
      <Link href="/superadmin/tenants" className="text-sm text-primary hover:underline">← Tenants</Link>
      <PageHeader
        title={tenant.name}
        subtitle={`${tenant.subdomain}.gestioname.app${tenant.custom_domain ? ` · ${tenant.custom_domain}` : ""}`}
        action={<div className="flex items-center gap-3"><Badge tone={STATUS_TONES[tenant.status] ?? "neutral"}>{tenant.status}</Badge><Button onClick={impersonate}>Impersonar</Button></div>}
      />
      {msg && <p className="text-sm text-[#0d6b50]">{msg}</p>}

      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Plan y estado</h3>
        <div className="flex flex-wrap items-end gap-3">
          <SelectField label="Plan" value={String(tenant.plan_id ?? "")} onChange={(v) => patch({ plan_id: v ? Number(v) : null })}
            options={[["", "Sin plan"], ...plans.map((p) => [String(p.id), p.name] as const)]} />
          <div className="flex gap-2">
            {tenant.status !== "active" && <Button variant="secondary" onClick={() => patch({ status: "active" })}>Activar</Button>}
            {tenant.status !== "suspended" && <Button variant="ghost" onClick={() => patch({ status: "suspended" })}>Suspender</Button>}
          </div>
        </div>
      </Card>

      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Módulos activos</h3>
        <div className="grid gap-3 sm:grid-cols-2">
          {modules.map((m) => (
            <div key={m.key} className="flex items-center justify-between rounded-[var(--radius-fluent)] border border-line p-3">
              <span className="text-sm text-ink">{m.label}</span>
              <Toggle on={m.enabled} onClick={() => toggleModule(m)} />
            </div>
          ))}
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="border-b border-line px-5 py-3"><h3 className="text-sm font-semibold text-primary">Usuarios</h3></div>
        <table className="w-full text-sm">
          <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
            <tr><th className="px-4 py-3 font-medium">Usuario</th><th className="px-4 py-3 font-medium">Rol</th><th className="px-4 py-3 font-medium">Último acceso</th><th className="px-4 py-3 font-medium">Estado</th><th className="px-4 py-3"></th></tr>
          </thead>
          <tbody className="divide-y divide-line">
            {users.map((u) => (
              <tr key={u.id}>
                <td className="px-4 py-3"><p className="font-medium text-ink">{u.name}</p><p className="text-xs text-ink-soft">{u.email}</p>
                  {resetLinks[u.id] && <input readOnly value={resetLinks[u.id]} onFocus={(e) => e.target.select()} className="mt-1 w-full rounded border border-line bg-canvas px-2 py-1 text-xs" />}
                </td>
                <td className="px-4 py-3">
                  <select value={u.roles[0] ?? ""} onChange={(e) => changeRole(u, e.target.value)} className="rounded border border-line bg-canvas px-2 py-1 text-xs outline-none focus:border-secondary">
                    {ROLES.map((r) => <option key={r} value={r}>{r}</option>)}
                    {u.roles[0] === "super-admin" && <option value="super-admin">super-admin</option>}
                  </select>
                </td>
                <td className="px-4 py-3 text-ink-soft">{fmtDateTime(u.last_login_at)}</td>
                <td className="px-4 py-3"><Badge tone={u.active ? "ok" : "neutral"}>{u.active ? "Activo" : "Inactivo"}</Badge></td>
                <td className="px-4 py-3">
                  <div className="flex justify-end gap-2 text-xs">
                    <button onClick={() => resetPassword(u)} className="text-primary hover:underline">Reset</button>
                    <button onClick={() => toggleActive(u)} className="text-amber-700 hover:underline">{u.active ? "Desactivar" : "Activar"}</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>

      <Card className="p-5">
        <h3 className="mb-1 text-sm font-semibold text-primary">Override de límites</h3>
        <p className="mb-3 text-sm text-ink-soft">Vacío = hereda del plan.</p>
        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
          {LIMIT_KEYS.map((k) => (
            <TextField key={k} label={k} type="number" value={override.limits?.[k] != null ? String(override.limits[k]) : ""} onChange={(v) => setLimit(k, v)} />
          ))}
        </div>
        <div className="mt-4"><Button onClick={saveOverride}>Guardar override</Button></div>
      </Card>

      <Card className="overflow-hidden">
        <div className="border-b border-line px-5 py-3"><h3 className="text-sm font-semibold text-primary">Historial</h3></div>
        {audit.length === 0 ? <p className="p-5 text-sm text-ink-soft">Sin acciones registradas.</p> : (
          <ul className="divide-y divide-line">
            {audit.map((a) => (
              <li key={a.id} className="flex items-center justify-between px-5 py-2 text-sm">
                <span><Badge tone="info">{a.action}</Badge> <span className="text-ink-soft">{a.actor ?? ""}</span></span>
                <span className="text-xs text-ink-soft">{formatDateTime(a.created_at)}</span>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  );
}
