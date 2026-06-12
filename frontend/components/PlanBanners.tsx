"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api } from "@/lib/api";

type Sub = { trial_days_left: number | null; usage: Record<string, { used: number; limit: number | null }> };

const LABELS: Record<string, string> = { companies: "empresas", employees: "empleados", entities: "entidades", members: "socios", users: "usuarios" };

/** Avisos de trial por caducar (<7 días) y límites de plan al 80%. */
export function PlanBanners() {
  const [sub, setSub] = useState<Sub | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Sub }>("/subscription");
        setSub(res.data);
      } catch {
        // sin datos: no mostramos banner
      }
    })();
  }, []);

  if (!sub) return null;

  const trial = sub.trial_days_left != null && sub.trial_days_left < 7;
  const near = Object.entries(sub.usage)
    .filter(([, u]) => u.limit != null && u.limit > 0 && u.used / u.limit >= 0.8)
    .map(([k]) => LABELS[k] ?? k);

  if (!trial && near.length === 0) return null;

  return (
    <div className="mb-6 space-y-2">
      {trial && (
        <div className="flex flex-wrap items-center justify-between gap-2 rounded-[var(--radius-fluent)] bg-amber-50 px-4 py-3 text-sm text-amber-800">
          <span>⏳ Tu periodo de prueba termina en <strong>{sub.trial_days_left} días</strong>.</span>
          <Link href="/admin/suscripcion" className="font-medium underline">Ver planes</Link>
        </div>
      )}
      {near.length > 0 && (
        <div className="flex flex-wrap items-center justify-between gap-2 rounded-[var(--radius-fluent)] bg-secondary/10 px-4 py-3 text-sm text-primary">
          <span>📈 Estás cerca del límite de tu plan en: <strong>{near.join(", ")}</strong>.</span>
          <Link href="/admin/suscripcion" className="font-medium underline">Actualizar plan</Link>
        </div>
      )}
    </div>
  );
}
