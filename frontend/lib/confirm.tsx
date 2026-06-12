"use client";

import { createContext, useCallback, useContext, useRef, useState } from "react";
import { Button } from "@/components/ui";

type Options = { title: string; message?: string; confirmLabel?: string; danger?: boolean };
type ConfirmFn = (opts: Options) => Promise<boolean>;

const Ctx = createContext<ConfirmFn | null>(null);

export function ConfirmProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<Options | null>(null);
  const resolver = useRef<((v: boolean) => void) | null>(null);

  const confirm = useCallback<ConfirmFn>((opts) => {
    setState(opts);
    return new Promise<boolean>((resolve) => { resolver.current = resolve; });
  }, []);

  function close(value: boolean) {
    resolver.current?.(value);
    resolver.current = null;
    setState(null);
  }

  return (
    <Ctx.Provider value={confirm}>
      {children}
      {state && (
        <div className="fixed inset-0 z-[110] flex items-center justify-center bg-primary/30 p-4 backdrop-blur-sm">
          <div className="w-full max-w-sm rounded-[var(--radius-fluent)] border border-line bg-surface p-6 shadow-[var(--shadow-fluent-lg)]">
            <h2 className="text-base font-semibold text-primary">{state.title}</h2>
            {state.message && <p className="mt-2 text-sm text-ink-soft">{state.message}</p>}
            <div className="mt-5 flex justify-end gap-2">
              <Button variant="ghost" onClick={() => close(false)}>Cancelar</Button>
              <button
                onClick={() => close(true)}
                className={`inline-flex items-center justify-center rounded-[var(--radius-fluent)] px-4 py-2 text-sm font-medium text-white transition-colors ${
                  state.danger === false ? "bg-primary hover:bg-primary-600" : "bg-red-600 hover:bg-red-700"
                }`}
              >
                {state.confirmLabel ?? "Eliminar"}
              </button>
            </div>
          </div>
        </div>
      )}
    </Ctx.Provider>
  );
}

export function useConfirm(): ConfirmFn {
  const ctx = useContext(Ctx);
  if (!ctx) throw new Error("useConfirm debe usarse dentro de <ConfirmProvider>");
  return ctx;
}
