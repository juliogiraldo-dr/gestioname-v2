"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { formatDate } from "@/lib/utils";
import { Badge, Button, Card, EmptyState, Modal, PageHeader, Skeleton, StatCard } from "@/components/ui";

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
const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

export default function SuscripcionPage() {
  const [sub, setSub] = useState<Subscription | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [showPlans, setShowPlans] = useState(false);

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
        action={<Button onClick={() => setShowPlans(true)}>Actualizar plan</Button>}
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

      {showPlans && (
        <Modal title="Planes disponibles" onClose={() => setShowPlans(false)}>
          <div className="grid gap-3 sm:grid-cols-2">
            {sub.plans.map((p) => {
              const current = p.slug === sub.plan?.slug;
              return (
                <Card key={p.slug} className={`p-4 ${current ? "border-secondary" : ""}`}>
                  <div className="flex items-center justify-between">
                    <h4 className="font-semibold text-ink">{p.name}</h4>
                    {current && <Badge tone="ok">Actual</Badge>}
                  </div>
                  <p className="mt-1 text-lg font-semibold text-primary">{eur(p.price_monthly)}<span className="text-xs font-normal text-ink-soft">/mes</span></p>
                  <p className="mt-2 text-xs text-ink-soft">
                    {(["companies", "employees", "entities", "members"] as const).map((k) => `${LABELS[k]}: ${p.limits[k] ?? "∞"}`).join(" · ")}
                  </p>
                </Card>
              );
            })}
          </div>
          <p className="mt-4 rounded-[var(--radius-fluent)] bg-secondary/10 px-4 py-3 text-sm text-primary">
            Para cambiar de plan, contacta con nosotros en <a href="mailto:info@datarecover.es" className="font-medium underline">info@datarecover.es</a>.
          </p>
        </Modal>
      )}
    </div>
  );
}
