"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { useConfirm } from "@/lib/confirm";
import { formatDateTime } from "@/lib/utils";
import { Badge, Button, Card, EmptyState, Skeleton, TextField } from "@/components/ui";
import type { FiscalPeriod } from "./shared";

export default function EjerciciosTab() {
  const toast = useToast();
  const confirm = useConfirm();
  const [periods, setPeriods] = useState<FiscalPeriod[] | null>(null);
  const [reloadKey, setReloadKey] = useState(0);
  const [newYear, setNewYear] = useState(String(new Date().getFullYear()));
  const [creating, setCreating] = useState(false);
  const [busy, setBusy] = useState<number | null>(null);

  const reload = useCallback(() => setReloadKey((k) => k + 1), []);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: FiscalPeriod[] }>("/accounting/fiscal-periods");
        if (active) setPeriods(res.data.slice().sort((a, b) => b.year - a.year));
      } catch {
        if (active) setPeriods([]);
      }
    })();
    return () => {
      active = false;
    };
  }, [reloadKey]);

  async function create() {
    const year = Number(newYear);
    if (!year || year < 2000 || year > 2100) {
      toast.warning("Indica un año válido.");
      return;
    }
    setCreating(true);
    try {
      await api("/accounting/fiscal-periods", { method: "POST", body: { year } });
      toast.success(`Ejercicio ${year} creado.`);
      reload();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo crear el ejercicio.");
    } finally {
      setCreating(false);
    }
  }

  async function close(period: FiscalPeriod) {
    const ok = await confirm({
      title: `Cerrar ejercicio ${period.year}`,
      message: "Cerrar el ejercicio impedirá registrar más asientos en ese año.",
      confirmLabel: "Cerrar",
    });
    if (!ok) return;
    setBusy(period.id);
    try {
      await api(`/accounting/fiscal-periods/${period.id}/close`, { method: "POST" });
      toast.success(`Ejercicio ${period.year} cerrado.`);
      reload();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo cerrar el ejercicio.");
    } finally {
      setBusy(null);
    }
  }

  async function reopen(period: FiscalPeriod) {
    setBusy(period.id);
    try {
      await api(`/accounting/fiscal-periods/${period.id}/reopen`, { method: "POST" });
      toast.success(`Ejercicio ${period.year} reabierto.`);
      reload();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo reabrir el ejercicio.");
    } finally {
      setBusy(null);
    }
  }

  if (periods === null) return <Skeleton rows={4} />;

  return (
    <div className="space-y-4">
      <Card className="flex flex-wrap items-end gap-3 p-4">
        <div className="w-32">
          <TextField label="Nuevo ejercicio" type="number" value={newYear} onChange={setNewYear} />
        </div>
        <Button onClick={create} disabled={creating}>{creating ? "Creando…" : "Crear ejercicio"}</Button>
      </Card>

      {periods.length === 0 ? (
        <EmptyState title="Sin ejercicios" message="Crea un ejercicio fiscal para empezar a registrar asientos." />
      ) : (
        <Card className="divide-y divide-line">
          {periods.map((p) => (
            <div key={p.id} className="flex items-center justify-between gap-4 p-4">
              <div className="flex items-center gap-3">
                <span className="text-lg font-semibold text-primary">{p.year}</span>
                {p.status === "open" ? (
                  <Badge tone="ok">Abierto</Badge>
                ) : (
                  <Badge tone="neutral">Cerrado</Badge>
                )}
                {p.status === "closed" && p.closed_at && (
                  <span className="text-xs text-ink-soft">Cerrado el {formatDateTime(p.closed_at)}</span>
                )}
              </div>
              {p.status === "open" ? (
                <Button variant="secondary" onClick={() => void close(p)} disabled={busy === p.id}>Cerrar</Button>
              ) : (
                <Button variant="secondary" onClick={() => void reopen(p)} disabled={busy === p.id}>Reabrir</Button>
              )}
            </div>
          ))}
        </Card>
      )}
    </div>
  );
}
