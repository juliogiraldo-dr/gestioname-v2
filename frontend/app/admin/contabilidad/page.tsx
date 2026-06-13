"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { PageHeader, SelectField } from "@/components/ui";
import { yearOptions, type Account } from "./shared";
import PlanCuentasTab from "./PlanCuentasTab";
import AsientosTab from "./AsientosTab";
import LibroMayorTab from "./LibroMayorTab";
import InformesTab from "./InformesTab";
import EjerciciosTab from "./EjerciciosTab";

type Tab = "cuentas" | "asientos" | "mayor" | "informes" | "ejercicios";

export default function ContabilidadPage() {
  const [tab, setTab] = useState<Tab>("cuentas");
  const [year, setYear] = useState(new Date().getFullYear());
  const [accounts, setAccounts] = useState<Account[]>([]);

  // La lista de cuentas la comparten los tabs de Asientos y Libro mayor.
  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: Account[] }>("/accounting/accounts");
        if (active) setAccounts(res.data);
      } catch {
        if (active) setAccounts([]);
      }
    })();
    return () => {
      active = false;
    };
  }, [tab]);

  return (
    <div>
      <PageHeader title="Contabilidad" subtitle="Plan de cuentas, asientos, libro mayor e informes contables" />

      <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div className="flex gap-1 border-b border-line">
          {([
            ["cuentas", "Plan de cuentas"],
            ["asientos", "Asientos"],
            ["mayor", "Libro mayor"],
            ["informes", "Informes"],
            ["ejercicios", "Ejercicios"],
          ] as const).map(([key, label]) => (
            <button
              key={key}
              onClick={() => setTab(key)}
              className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
                tab === key
                  ? "border-secondary text-primary"
                  : "border-transparent text-ink-soft hover:text-ink"
              }`}
            >
              {label}
            </button>
          ))}
        </div>
        <SelectField
          label="Ejercicio"
          value={String(year)}
          onChange={(v) => setYear(Number(v))}
          options={yearOptions(new Date().getFullYear())}
          className="w-32"
        />
      </div>

      {tab === "cuentas" && <PlanCuentasTab />}
      {tab === "asientos" && <AsientosTab year={year} accounts={accounts} />}
      {tab === "mayor" && <LibroMayorTab year={year} accounts={accounts} />}
      {tab === "informes" && <InformesTab year={year} />}
      {tab === "ejercicios" && <EjerciciosTab />}
    </div>
  );
}
