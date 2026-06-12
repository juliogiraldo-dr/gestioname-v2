"use client";

import { createContext, useContext, useEffect, useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { api } from "./api";

export type ActiveCompany = { id: string; name: string };

type CompanyCtx = {
  companies: ActiveCompany[];
  activeId: string;
  setActiveId: (id: string) => void;
};

const Ctx = createContext<CompanyCtx | null>(null);
const STORAGE_KEY = "gm_active_company";

/**
 * Empresa activa del dashboard. El usuario puede tener varias empresas del tenant;
 * la activa se guarda en localStorage y filtra el contexto (empleados, fichajes…).
 */
export function CompanyProvider({ children }: { children: React.ReactNode }) {
  const [companies, setCompanies] = useState<ActiveCompany[]>([]);
  const [activeId, setActive] = useState("");
  const queryClient = useQueryClient();

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: ActiveCompany[] }>("/companies");
      const stored = typeof window !== "undefined" ? window.localStorage.getItem(STORAGE_KEY) : null;
      const valid = res.data.find((c) => c.id === stored);
      setCompanies(res.data);
      setActive(valid?.id ?? res.data[0]?.id ?? "");
    })();
  }, []);

  function setActiveId(id: string) {
    setActive(id);
    if (typeof window !== "undefined") window.localStorage.setItem(STORAGE_KEY, id);
    // Recarga los listados al cambiar de empresa (los que no cachean por companyId).
    void queryClient.invalidateQueries();
  }

  return <Ctx.Provider value={{ companies, activeId, setActiveId }}>{children}</Ctx.Provider>;
}

/** Devuelve el contexto de empresa activa, o null si no hay provider (p. ej. en el portal). */
export function useActiveCompany(): CompanyCtx | null {
  return useContext(Ctx);
}
