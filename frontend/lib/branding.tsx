"use client";

import { createContext, useContext, useEffect, useState } from "react";
import { api } from "./api";

export type Branding = {
  app_name: string;
  primary_color: string;
  logo_path: string | null;
  custom_domain: string | null;
};

const DEFAULTS: Branding = {
  app_name: "Gestioname",
  primary_color: "#0F2756",
  logo_path: null,
  custom_domain: null,
};

const BrandingContext = createContext<Branding>(DEFAULTS);

/**
 * Carga el branding del tenant (endpoint público) y lo aplica: color principal vía la
 * variable CSS `--color-primary`, nombre y logo a través del contexto. Si no hay branding
 * configurado, usa los valores por defecto de Gestioname.
 */
export function BrandingProvider({ children }: { children: React.ReactNode }) {
  const [branding, setBranding] = useState<Branding>(DEFAULTS);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Branding }>("/branding", { auth: false });
        setBranding({ ...DEFAULTS, ...res.data });
        if (res.data.primary_color) {
          document.documentElement.style.setProperty("--color-primary", res.data.primary_color);
        }
        if (res.data.app_name) document.title = res.data.app_name;
      } catch {
        // Sin branding: se mantienen los valores por defecto.
      }
    })();
  }, []);

  return <BrandingContext.Provider value={branding}>{children}</BrandingContext.Provider>;
}

export function useBranding(): Branding {
  return useContext(BrandingContext);
}
