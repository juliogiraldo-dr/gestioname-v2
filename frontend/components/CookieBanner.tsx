"use client";

import { useState } from "react";
import Link from "next/link";

const STORAGE_KEY = "gm_cookies_ok";

/** Lee la aceptación de cookies de localStorage de forma segura en SSR. */
function readAccepted(): boolean {
  if (typeof window === "undefined") return true; // en SSR no se pinta el banner
  try {
    return window.localStorage.getItem(STORAGE_KEY) === "1";
  } catch {
    return true;
  }
}

/**
 * Banner fijo inferior con aviso de cookies técnicas. Solo se muestra si el usuario
 * no ha aceptado aún. La aceptación se persiste en localStorage ("gm_cookies_ok").
 */
export function CookieBanner() {
  // Inicializador perezoso: evita setState síncrono dentro de useEffect.
  const [accepted, setAccepted] = useState<boolean>(() => readAccepted());

  if (accepted) return null;

  function accept() {
    try {
      window.localStorage.setItem(STORAGE_KEY, "1");
    } catch {
      // Si no se puede persistir, al menos ocultamos el banner en esta sesión.
    }
    setAccepted(true);
  }

  return (
    <div className="fixed inset-x-0 bottom-0 z-50 px-4 pb-4">
      <div className="mx-auto flex max-w-3xl flex-col items-start gap-3 rounded-[var(--radius-fluent)] border border-line bg-surface p-4 shadow-[var(--shadow-fluent-lg)] sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-ink-soft">
          Usamos solo cookies técnicas necesarias para el funcionamiento.{" "}
          <Link href="/legal/cookies" className="font-medium text-primary hover:underline">
            Más información
          </Link>
          .
        </p>
        <button
          type="button"
          onClick={accept}
          className="inline-flex shrink-0 items-center justify-center rounded-[var(--radius-fluent)] bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-600"
        >
          Aceptar
        </button>
      </div>
    </div>
  );
}
