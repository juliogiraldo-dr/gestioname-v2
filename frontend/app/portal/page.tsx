"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Card, PageHeader, Spinner, StatCard } from "@/components/ui";

type Vacations = { year: number; available: number; requested: number; approved: number; remaining: number };
type LeaveRow = { id: string; status: string; date_start: string; date_end: string; total_days: number | null };

export default function InicioPage() {
  const { profile } = useAuth();
  const [vac, setVac] = useState<Vacations | null>(null);
  const [leaves, setLeaves] = useState<LeaveRow[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const [v, l] = await Promise.all([
          api<{ data: Vacations }>("/me/vacations"),
          api<{ data: LeaveRow[] }>("/me/leave-requests"),
        ]);
        setVac(v.data);
        setLeaves(l.data);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) return <Spinner />;

  const pending = leaves.filter((l) => l.status === "pendiente").length;

  return (
    <div>
      <PageHeader title={`Hola, ${profile?.name?.split(" ")[0] ?? ""}`} subtitle="Resumen de tu actividad" />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Vacaciones restantes" value={vac ? `${vac.remaining}` : "—"} hint={vac ? `de ${vac.available} días` : undefined} />
        <StatCard label="Días aprobados" value={vac ? vac.approved : "—"} hint={`Año ${vac?.year ?? ""}`} />
        <StatCard label="Solicitudes pendientes" value={pending} />
        <StatCard label="Puesto" value={<span className="text-xl">{profile?.employee?.job_position ?? "—"}</span>} />
      </div>

      <Card className="mt-6 p-6">
        <h2 className="mb-4 text-sm font-semibold text-primary">Últimas solicitudes</h2>
        {leaves.length === 0 ? (
          <p className="text-sm text-ink-soft">Aún no has realizado solicitudes.</p>
        ) : (
          <ul className="divide-y divide-line">
            {leaves.slice(0, 5).map((l) => (
              <li key={l.id} className="flex items-center justify-between py-2.5 text-sm">
                <span>
                  {l.date_start} → {l.date_end}
                </span>
                <span className="text-ink-soft">{l.total_days ?? "—"} días · {l.status}</span>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  );
}
