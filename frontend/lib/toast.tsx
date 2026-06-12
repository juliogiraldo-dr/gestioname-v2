"use client";

import { createContext, useCallback, useContext, useState } from "react";

type Kind = "success" | "error" | "warning" | "info";
type Toast = { id: number; kind: Kind; message: string };

type ToastCtx = {
  push: (kind: Kind, message: string) => void;
  success: (m: string) => void;
  error: (m: string) => void;
  warning: (m: string) => void;
  info: (m: string) => void;
};

const Ctx = createContext<ToastCtx | null>(null);

const STYLES: Record<Kind, string> = {
  success: "border-l-accent bg-white text-ink",
  error: "border-l-red-500 bg-white text-ink",
  warning: "border-l-amber-500 bg-white text-ink",
  info: "border-l-secondary bg-white text-ink",
};
const ICONS: Record<Kind, string> = { success: "✓", error: "⚠", warning: "!", info: "i" };

let nextId = 1;

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const remove = useCallback((id: number) => setToasts((t) => t.filter((x) => x.id !== id)), []);

  const push = useCallback((kind: Kind, message: string) => {
    const id = nextId++;
    setToasts((t) => [...t, { id, kind, message }]);
    setTimeout(() => remove(id), 4000);
  }, [remove]);

  const value: ToastCtx = {
    push,
    success: (m) => push("success", m),
    error: (m) => push("error", m),
    warning: (m) => push("warning", m),
    info: (m) => push("info", m),
  };

  return (
    <Ctx.Provider value={value}>
      {children}
      <div className="pointer-events-none fixed right-4 top-4 z-[100] flex w-[min(92vw,360px)] flex-col gap-2">
        {toasts.map((t) => (
          <div
            key={t.id}
            onClick={() => remove(t.id)}
            className={`pointer-events-auto flex cursor-pointer items-start gap-3 rounded-[var(--radius-fluent)] border border-line border-l-4 px-4 py-3 text-sm shadow-[var(--shadow-fluent-lg)] ${STYLES[t.kind]}`}
            role="status"
          >
            <span className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-canvas text-xs font-bold">{ICONS[t.kind]}</span>
            <span className="flex-1">{t.message}</span>
          </div>
        ))}
      </div>
    </Ctx.Provider>
  );
}

export function useToast(): ToastCtx {
  const ctx = useContext(Ctx);
  if (!ctx) throw new Error("useToast debe usarse dentro de <ToastProvider>");
  return ctx;
}
