"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { PageHeader } from "@/components/ui";
import {
  CalendariosSection,
  CentrosSection,
  ConveniosSection,
  EmpresasSection,
  FestivosSection,
  HitosSection,
  MarcaBlancaSection,
  ModulosSection,
} from "./sections";

const BASE_TABS = [
  ["modulos", "Módulos"],
  ["empresas", "Empresas y grupos"],
  ["centros", "Centros de trabajo"],
  ["convenios", "Convenios"],
  ["hitos", "Hitos de fichaje"],
  ["festivos", "Festivos"],
  ["calendarios", "Calendarios"],
] as const;

type Module = { key: string; enabled: boolean };

export default function ConfiguracionPage() {
  const [tab, setTab] = useState("modulos");
  const [whiteLabel, setWhiteLabel] = useState(false);

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: Module[] }>("/tenant-modules");
      setWhiteLabel(res.data.some((m) => m.key === "white_label" && m.enabled));
    })();
  }, []);

  const tabs = [...BASE_TABS, ...(whiteLabel ? [["marca_blanca", "Marca blanca"] as const] : [])];

  return (
    <div>
      <PageHeader title="Configuración" subtitle="Módulos, organización y parámetros del tenant" />

      <div className="mb-6 flex flex-wrap gap-1 border-b border-line">
        {tabs.map(([key, label]) => (
          <button
            key={key}
            onClick={() => setTab(key)}
            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
              tab === key ? "border-secondary text-primary" : "border-transparent text-ink-soft hover:text-ink"
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {tab === "modulos" && <ModulosSection />}
      {tab === "empresas" && <EmpresasSection />}
      {tab === "centros" && <CentrosSection />}
      {tab === "convenios" && <ConveniosSection />}
      {tab === "hitos" && <HitosSection />}
      {tab === "festivos" && <FestivosSection />}
      {tab === "calendarios" && <CalendariosSection />}
      {tab === "marca_blanca" && whiteLabel && <MarcaBlancaSection />}
    </div>
  );
}
