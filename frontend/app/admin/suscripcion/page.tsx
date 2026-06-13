"use client";

import { useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { formatDate } from "@/lib/utils";
import { useToast } from "@/lib/toast";
import { Badge, Button, Card, EmptyState, Modal, PageHeader, SelectField, Skeleton, StatCard, TextField, Toggle } from "@/components/ui";

type Usage = Record<string, { used: number; limit: number | null }>;
type PlanRow = { name: string; slug: string; price_monthly: number; price_yearly: number | null; limits: Record<string, number | null>; modules_allowed: string[] };
type Subscription = {
  plan: { name: string; slug: string; price_monthly: number } | null;
  trial_ends_at: string | null;
  trial_days_left: number | null;
  usage: Usage;
  plans: PlanRow[];
};

const LABELS: Record<string, string> = { companies: "Empresas", employees: "Empleados", entities: "Entidades", members: "Socios", users: "Usuarios" };
const LIMIT_KEYS = ["companies", "employees", "entities", "members", "users"] as const;
const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

/** Precio anual total del plan: usa price_yearly si existe, o estima 12 meses con ~17% de descuento. */
function yearlyTotal(p: PlanRow): number {
  return p.price_yearly ?? Math.round(p.price_monthly * 12 * 0.83);
}

export default function SuscripcionPage() {
  const [sub, setSub] = useState<Subscription | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [annual, setAnnual] = useState(false);
  const [showUpgrade, setShowUpgrade] = useState(false);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Subscription }>("/subscription");
        setSub(res.data);
      } catch {
        setError("No se pudo cargar la suscripción.");
      }
    })();
  }, []);

  if (error) return <div><PageHeader title="Suscripción" /><EmptyState title="Error" message={error} /></div>;
  if (!sub) return <div><PageHeader title="Suscripción" /><Skeleton rows={4} /></div>;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Suscripción"
        subtitle="Tu plan, consumo y límites"
        action={<Button onClick={() => setShowUpgrade(true)}>Solicitar upgrade</Button>}
      />

      <div className="grid gap-4 sm:grid-cols-3">
        <StatCard label="Plan actual" value={sub.plan?.name ?? "—"} hint={sub.plan ? `${eur(sub.plan.price_monthly)}/mes` : undefined} />
        <StatCard label="Estado del trial" value={sub.trial_days_left != null ? `${sub.trial_days_left} días` : "—"} hint={sub.trial_ends_at ? `hasta ${formatDate(sub.trial_ends_at)}` : "Sin trial activo"} />
        <StatCard label="Renovación" value={sub.trial_ends_at ? formatDate(sub.trial_ends_at) : "Mensual"} />
      </div>

      <Card className="p-5">
        <h3 className="mb-4 text-sm font-semibold text-primary">Consumo del plan</h3>
        <div className="space-y-4">
          {Object.entries(sub.usage).map(([key, u]) => {
            const pct = u.limit ? Math.min(100, Math.round((u.used / u.limit) * 100)) : 0;
            const near = u.limit != null && u.used / u.limit >= 0.8;
            return (
              <div key={key}>
                <div className="mb-1 flex items-center justify-between text-sm">
                  <span className="text-ink">{LABELS[key] ?? key}</span>
                  <span className={near ? "font-medium text-amber-700" : "text-ink-soft"}>
                    {u.used} {u.limit != null ? `/ ${u.limit}` : "· ilimitado"}
                  </span>
                </div>
                {u.limit != null && (
                  <div className="h-2 w-full overflow-hidden rounded-full bg-line">
                    <div className={`h-full rounded-full ${near ? "bg-amber-500" : "bg-secondary"}`} style={{ width: `${pct}%` }} />
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </Card>

      <Card className="p-5">
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
          <h3 className="text-sm font-semibold text-primary">Planes disponibles</h3>
          <div className="flex items-center gap-3 text-sm">
            <span className={annual ? "text-ink-soft" : "font-medium text-ink"}>Mensual</span>
            <Toggle on={annual} onClick={() => setAnnual((a) => !a)} />
            <span className={annual ? "font-medium text-ink" : "text-ink-soft"}>Anual</span>
          </div>
        </div>

        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {sub.plans.map((p) => {
            const current = p.slug === sub.plan?.slug;
            const perMonth = annual ? yearlyTotal(p) / 12 : p.price_monthly;
            const savings = p.price_monthly * 12 - yearlyTotal(p);
            return (
              <Card key={p.slug} className={`flex flex-col p-4 ${current ? "border-secondary ring-1 ring-secondary/40" : ""}`}>
                <div className="flex items-center justify-between gap-2">
                  <h4 className="font-semibold text-ink">{p.name}</h4>
                  {current && <Badge tone="ok">Plan actual</Badge>}
                </div>
                <p className="mt-2 text-2xl font-semibold text-primary">
                  {eur(perMonth)}<span className="text-xs font-normal text-ink-soft">/mes</span>
                </p>
                {annual ? (
                  <p className="mt-1 text-xs text-ink-soft">
                    {eur(yearlyTotal(p))}/año{savings > 0 && <span className="ml-1 font-medium text-[#0d6b50]">· ahorras {eur(savings)}/año</span>}
                  </p>
                ) : (
                  <p className="mt-1 text-xs text-ink-soft">facturación mensual</p>
                )}

                <dl className="mt-3 space-y-1 text-xs">
                  {LIMIT_KEYS.map((k) => (
                    <div key={k} className="flex items-center justify-between">
                      <dt className="text-ink-soft">{LABELS[k]}</dt>
                      <dd className="font-medium text-ink">{p.limits[k] ?? "∞"}</dd>
                    </div>
                  ))}
                </dl>

                {p.modules_allowed.length > 0 && (
                  <div className="mt-3 flex flex-wrap gap-1">
                    {p.modules_allowed.map((m) => (
                      <Badge key={m} tone="info">{m}</Badge>
                    ))}
                  </div>
                )}
              </Card>
            );
          })}
        </div>
      </Card>

      {showUpgrade && <UpgradeModal plans={sub.plans} currentSlug={sub.plan?.slug ?? null} onClose={() => setShowUpgrade(false)} />}
    </div>
  );
}

function UpgradeModal({ plans, currentSlug, onClose }: { plans: PlanRow[]; currentSlug: string | null; onClose: () => void }) {
  const defaultPlan = plans.find((p) => p.slug !== currentSlug)?.slug ?? plans[0]?.slug ?? "";
  const [form, setForm] = useState({ name: "", email: "", plan: defaultPlan });
  const [busy, setBusy] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const toast = useToast();
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  async function submit() {
    setErr(null); setBusy(true);
    try {
      await api("/subscription/upgrade-request", { method: "POST", body: form });
      toast.success("Solicitud enviada. Te contactaremos en breve.");
      onClose();
    } catch (e) {
      const msg = e instanceof ApiError ? e.message : "No se pudo enviar la solicitud";
      setErr(msg);
      toast.error(msg);
    } finally {
      setBusy(false);
    }
  }

  return (
    <Modal title="Solicitar upgrade de plan" onClose={onClose}>
      <p className="mb-4 text-sm text-ink-soft">Déjanos tus datos y el plan que te interesa; nuestro equipo te contactará para gestionar el cambio.</p>
      <div className="grid gap-3 sm:grid-cols-2">
        <TextField label="Nombre" value={form.name} onChange={(v) => set("name", v)} />
        <TextField label="Email" type="email" value={form.email} onChange={(v) => set("email", v)} />
        <SelectField label="Plan deseado" value={form.plan} onChange={(v) => set("plan", v)} options={plans.map((p) => [p.slug, p.name] as const)} className="sm:col-span-2" />
      </div>
      {err && <p className="mt-3 text-sm text-red-700">{err}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.name || !form.email || !form.plan}>{busy ? "Enviando…" : "Enviar solicitud"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}
