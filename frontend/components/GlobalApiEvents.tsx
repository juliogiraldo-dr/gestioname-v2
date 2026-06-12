"use client";

import { useEffect } from "react";
import { getToken, setToken } from "@/lib/api";
import { useToast } from "@/lib/toast";

/** Escucha eventos globales de la API: sesión expirada (401) y límite de plan (402). */
export function GlobalApiEvents() {
  const toast = useToast();

  useEffect(() => {
    function onUnauthorized() {
      if (!getToken()) return; // ya estaba sin sesión
      setToken(null);
      toast.error("Tu sesión ha expirado. Vuelve a iniciar sesión.");
      setTimeout(() => { window.location.assign("/login"); }, 800);
    }
    function onPlanLimit(e: Event) {
      const detail = (e as CustomEvent).detail as { message?: string; limit?: number; resource?: string } | undefined;
      toast.warning(detail?.message ?? "Has alcanzado un límite de tu plan.");
    }

    window.addEventListener("gm:unauthorized", onUnauthorized);
    window.addEventListener("gm:plan-limit", onPlanLimit);
    return () => {
      window.removeEventListener("gm:unauthorized", onUnauthorized);
      window.removeEventListener("gm:plan-limit", onPlanLimit);
    };
  }, [toast]);

  return null;
}
