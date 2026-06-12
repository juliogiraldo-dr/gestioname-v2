"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { Badge, Button, Card, EmptyState, Modal, PageHeader, Skeleton, TextField, Toggle } from "@/components/ui";

type Plan = {
  id: number; name: string; slug: string; price_monthly: number; price_yearly: number | null;
  is_public: boolean; limits: Record<string, number | null>; modules_allowed: string[];
};

const LIMIT_KEYS = ["companies", "employees", "entities", "members", "users"] as const;
const MODULE_KEYS = ["rrhh", "socios", "tesoreria", "comunicaciones", "informes_avanzados", "white_label", "nominas", "multitenant"] as const;
const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

export default function PlanesPage() {
  const [plans, setPlans] = useState<Plan[] | null>(null);
  const [editing, setEditing] = useState<Plan | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    setPlans(null);
    try {
      const res = await api<{ data: Plan[] }>("/superadmin/plans");
      setPlans(res.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "No se pudieron cargar los planes.");
    }
  }, []);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function remove(id: number) {
    await api(`/superadmin/plans/${id}`, { method: "DELETE" });
    await load();
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Planes" subtitle="Catálogo de suscripciones"
        action={<Button onClick={() => { setEditing(null); setShowForm(true); }}>Nuevo plan</Button>} />

      {error ? (
        <EmptyState title="No se pudieron cargar los planes" message={error} action={<Button onClick={() => void load()}>Reintentar</Button>} />
      ) : !plans ? <Skeleton rows={4} /> : (
        <div className="grid gap-4 lg:grid-cols-2">
          {plans.map((p) => (
            <Card key={p.id} className="p-5">
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="font-semibold text-ink">{p.name}</h3>
                    {p.is_public ? <Badge tone="ok">Público</Badge> : <Badge tone="neutral">Oculto</Badge>}
                  </div>
                  <p className="mt-1 text-sm text-ink-soft">{eur(p.price_monthly)}/mes</p>
                </div>
                <div className="flex gap-3">
                  <button onClick={() => { setEditing(p); setShowForm(true); }} className="text-xs font-medium text-primary hover:underline">Editar</button>
                  <button onClick={() => remove(p.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                </div>
              </div>
              <div className="mt-3 text-xs text-ink-soft">
                {LIMIT_KEYS.map((k) => `${k}: ${p.limits?.[k] ?? "∞"}`).join(" · ")}
              </div>
              <div className="mt-2 flex flex-wrap gap-1">
                {p.modules_allowed.map((m) => <Badge key={m} tone="info">{m}</Badge>)}
              </div>
            </Card>
          ))}
        </div>
      )}

      {showForm && <PlanForm plan={editing} onClose={() => setShowForm(false)} onSaved={() => { setShowForm(false); void load(); }} />}
    </div>
  );
}

function PlanForm({ plan, onClose, onSaved }: { plan: Plan | null; onClose: () => void; onSaved: () => void }) {
  const [form, setForm] = useState({
    name: plan?.name ?? "", slug: plan?.slug ?? "", price_monthly: String(plan?.price_monthly ?? 0),
    price_yearly: plan?.price_yearly != null ? String(plan.price_yearly) : "", is_public: plan?.is_public ?? true,
    limits: { ...(plan?.limits ?? {}) } as Record<string, number | null>,
    modules: plan?.modules_allowed ?? [],
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const setLimit = (k: string, v: string) => setForm((p) => ({ ...p, limits: { ...p.limits, [k]: v === "" ? null : Number(v) } }));
  const toggleModule = (k: string) => setForm((p) => ({ ...p, modules: p.modules.includes(k) ? p.modules.filter((m) => m !== k) : [...p.modules, k] }));

  async function submit() {
    setError(null); setBusy(true);
    try {
      const body = {
        name: form.name, slug: form.slug, price_monthly: Number(form.price_monthly),
        price_yearly: form.price_yearly === "" ? null : Number(form.price_yearly),
        is_public: form.is_public, limits: form.limits, modules_allowed: form.modules,
      };
      await api(plan ? `/superadmin/plans/${plan.id}` : "/superadmin/plans", { method: plan ? "PUT" : "POST", body });
      onSaved();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo guardar"); } finally { setBusy(false); }
  }

  return (
    <Modal title={plan ? "Editar plan" : "Nuevo plan"} onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-2">
        <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} />
        <TextField label="Slug" value={form.slug} onChange={(v) => setForm((p) => ({ ...p, slug: v }))} />
        <TextField label="Precio mensual (€)" type="number" value={form.price_monthly} onChange={(v) => setForm((p) => ({ ...p, price_monthly: v }))} />
        <TextField label="Precio anual (€)" type="number" value={form.price_yearly} onChange={(v) => setForm((p) => ({ ...p, price_yearly: v }))} />
      </div>
      <div className="mt-3 flex items-center gap-2">
        <Toggle on={form.is_public} onClick={() => setForm((p) => ({ ...p, is_public: !p.is_public }))} label="Plan público" />
      </div>

      <h4 className="mb-2 mt-5 text-sm font-semibold text-primary">Límites (vacío = ilimitado)</h4>
      <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {LIMIT_KEYS.map((k) => (
          <TextField key={k} label={k} type="number" value={form.limits[k] != null ? String(form.limits[k]) : ""} onChange={(v) => setLimit(k, v)} />
        ))}
      </div>

      <h4 className="mb-2 mt-5 text-sm font-semibold text-primary">Módulos permitidos</h4>
      <div className="flex flex-wrap gap-3">
        {MODULE_KEYS.map((k) => (
          <label key={k} className="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" checked={form.modules.includes(k)} onChange={() => toggleModule(k)} className="h-4 w-4 rounded border-line text-primary focus:ring-secondary/40" />
            {k}
          </label>
        ))}
      </div>

      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.name || !form.slug}>{busy ? "Guardando…" : "Guardar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}
