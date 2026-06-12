"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api, ApiError } from "@/lib/api";
import { Button, Card, Field, PageHeader, SelectField, TextField } from "@/components/ui";

type Plan = { slug: string; name: string };

const TYPES = [["empresa", "Empresa"], ["entidad", "Entidad/Asociación"], ["ambas", "Ambas"]] as const;

export default function NuevoTenantPage() {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [form, setForm] = useState({ name: "", type: "ambas", subdomain: "", admin_email: "", plan: "free", trial_days: "30" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [created, setCreated] = useState<string | null>(null);
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: Plan[] }>("/superadmin/plans");
      setPlans(res.data);
    })();
  }, []);

  async function submit() {
    setError(null); setBusy(true);
    try {
      const res = await api<{ url: string }>("/superadmin/tenants", {
        method: "POST",
        body: {
          name: form.name, type: form.type, subdomain: form.subdomain.toLowerCase(),
          admin_email: form.admin_email, plan: form.plan, trial_days: Number(form.trial_days),
        },
      });
      setCreated(res.url);
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo crear"); } finally { setBusy(false); }
  }

  if (created) {
    return (
      <div>
        <PageHeader title="Tenant creado" />
        <Card className="p-8 text-center">
          <p className="text-sm text-ink-soft">Provisionado en <span className="font-medium text-ink">{created}</span>. Se ha enviado un magic link al administrador.</p>
          <div className="mt-4"><Link href="/superadmin/tenants"><Button>Volver a tenants</Button></Link></div>
        </Card>
      </div>
    );
  }

  return (
    <div className="max-w-2xl">
      <PageHeader title="Nuevo tenant" subtitle="Provisiona un cliente nuevo" />
      <Card className="p-5">
        <div className="grid gap-4 sm:grid-cols-2">
          <TextField label="Nombre" value={form.name} onChange={(v) => set("name", v)} />
          <SelectField label="Tipo" value={form.type} onChange={(v) => set("type", v)} options={TYPES} />
          <Field label="Subdominio">
            <div className="flex items-center gap-2">
              <input value={form.subdomain} onChange={(e) => set("subdomain", e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ""))}
                className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30" />
              <span className="whitespace-nowrap text-sm text-ink-soft">.gestioname.app</span>
            </div>
          </Field>
          <TextField label="Email admin" type="email" value={form.admin_email} onChange={(v) => set("admin_email", v)} />
          <SelectField label="Plan" value={form.plan} onChange={(v) => set("plan", v)} options={plans.map((p) => [p.slug, p.name] as const)} />
          <TextField label="Días de trial" type="number" value={form.trial_days} onChange={(v) => set("trial_days", v)} />
        </div>
        {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
        <div className="mt-4 flex gap-2">
          <Button onClick={submit} disabled={busy || !form.name || !form.subdomain || !form.admin_email}>{busy ? "Provisionando…" : "Crear tenant"}</Button>
          <Link href="/superadmin/tenants"><Button variant="ghost">Cancelar</Button></Link>
        </div>
      </Card>
    </div>
  );
}
