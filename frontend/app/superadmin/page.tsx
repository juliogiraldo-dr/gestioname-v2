"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { Button, EmptyState, PageHeader, Skeleton, StatCard } from "@/components/ui";

type Kpis = {
  tenants_total: number;
  tenants_active: number;
  tenants_trial: number;
  tenants_suspended: number;
  mrr: number;
  employees_total: number;
  members_total: number;
};

const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

export default function SuperAdminDashboard() {
  const [kpis, setKpis] = useState<Kpis | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    setKpis(null);
    try {
      const res = await api<{ data: Kpis }>("/superadmin/dashboard");
      setKpis(res.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "No se pudo cargar el panel.");
    }
  }, []);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  return (
    <div>
      <PageHeader title="Operador" subtitle="Visión global de Gestioname" />
      {error ? (
        <EmptyState title="No se pudo cargar el panel" message={error} action={<Button onClick={() => void load()}>Reintentar</Button>} />
      ) : !kpis ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Skeleton rows={2} /><Skeleton rows={2} /><Skeleton rows={2} /><Skeleton rows={2} />
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard label="MRR" value={eur(kpis.mrr)} hint="Ingreso mensual recurrente" />
          <StatCard label="Tenants activos" value={kpis.tenants_active} hint={`${kpis.tenants_total} en total`} />
          <StatCard label="En trial" value={kpis.tenants_trial} />
          <StatCard label="Suspendidos" value={kpis.tenants_suspended} />
          <StatCard label="Empleados (global)" value={kpis.employees_total} />
          <StatCard label="Socios (global)" value={kpis.members_total} />
        </div>
      )}
    </div>
  );
}
