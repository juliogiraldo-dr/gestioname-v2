"use client";

import { useEffect, useMemo, useState } from "react";
import { api } from "@/lib/api";
import { Badge, Card, EmptyState, PageHeader, Spinner } from "@/components/ui";

type Row = { id: string; clocked_at: string; work_mode?: string | null; milestone?: { type: string } };
type Day = { date: string; label: string; first: string | null; last: string | null; hours: number; incomplete: boolean; empty: boolean; mode: string | null };

const DAY_LABELS = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
const MODE_LABEL: Record<string, string> = { presencial: "Oficina", teletrabajo: "Teletrabajo" };

/** Lunes de la semana de `d`. */
function monday(d: Date): Date {
  const x = new Date(d);
  const day = (x.getDay() + 6) % 7; // 0 = lunes
  x.setDate(x.getDate() - day);
  x.setHours(0, 0, 0, 0);
  return x;
}

export default function MisFichajesPage() {
  const [rows, setRows] = useState<Row[] | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: Row[] }>("/me/attendances");
        if (active) setRows(res.data);
      } catch {
        if (active) setError(true);
      }
    })();
    return () => {
      active = false;
    };
  }, []);

  const week = useMemo<Day[]>(() => {
    if (!rows) return [];
    const start = monday(new Date());
    const byDate = new Map<string, { min: number; type: string; mode: string | null }[]>();
    for (const r of rows) {
      const d = new Date(r.clocked_at);
      const key = d.toISOString().slice(0, 10);
      if (!byDate.has(key)) byDate.set(key, []);
      byDate.get(key)!.push({ min: d.getHours() * 60 + d.getMinutes(), type: r.milestone?.type ?? "", mode: r.work_mode ?? null });
    }
    return Array.from({ length: 7 }, (_, i) => {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      const key = d.toISOString().slice(0, 10);
      const items = (byDate.get(key) ?? []).sort((a, b) => a.min - b.min);
      let worked = 0;
      let open: number | null = null;
      let incomplete = false;
      let first: number | null = null;
      let last: number | null = null;
      let mode: string | null = null;
      for (const it of items) {
        if (it.mode) mode = it.mode;
        if (it.type === "entrada") { open = it.min; if (first === null) first = it.min; }
        else if (it.type === "salida") { if (open !== null) { worked += it.min - open; open = null; } last = it.min; }
      }
      if (open !== null) incomplete = true;
      return {
        date: key, label: DAY_LABELS[i], first: first !== null ? fmt(first) : null,
        last: last !== null ? fmt(last) : null, hours: worked / 60, incomplete, empty: items.length === 0, mode,
      };
    });
  }, [rows]);

  const pendingExit = week.some((d) => d.incomplete);
  const total = week.reduce((a, d) => a + d.hours, 0);

  if (error) {
    return (
      <div>
        <PageHeader title="Mis fichajes" subtitle="Resumen de tu semana" />
        <EmptyState title="Sin ficha de empleado" message="No tienes ningún empleado vinculado a esta cuenta." />
      </div>
    );
  }

  return (
    <div>
      <PageHeader title="Mis fichajes" subtitle="Resumen de tu semana" />

      {pendingExit && (
        <div className="mb-4 rounded-[var(--radius-fluent)] bg-amber-50 px-4 py-3 text-sm text-amber-800">
          ⚠ Tienes un día con entrada sin salida. Revisa tus fichajes.
        </div>
      )}

      {!rows ? <Spinner /> : (
        <Card className="overflow-hidden">
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr><th className="px-5 py-3 font-medium">Día</th><th className="px-5 py-3 font-medium">Entrada</th><th className="px-5 py-3 font-medium">Salida</th><th className="px-5 py-3 font-medium">Modalidad</th><th className="px-5 py-3 text-right font-medium">Horas</th></tr>
            </thead>
            <tbody className="divide-y divide-line">
              {week.map((d) => (
                <tr key={d.date} className={d.empty ? "text-ink-soft" : ""}>
                  <td className="px-5 py-3 font-medium">{d.label} <span className="text-xs text-ink-soft">{d.date.slice(8)}/{d.date.slice(5, 7)}</span></td>
                  <td className="px-5 py-3">{d.first ?? "—"}</td>
                  <td className="px-5 py-3">{d.incomplete ? <Badge tone="warn">falta salida</Badge> : (d.last ?? "—")}</td>
                  <td className="px-5 py-3">{d.mode ? <Badge tone={d.mode === "teletrabajo" ? "info" : "neutral"}>{MODE_LABEL[d.mode] ?? d.mode}</Badge> : <span className="text-ink-soft">—</span>}</td>
                  <td className="px-5 py-3 text-right font-medium text-ink">{d.hours > 0 ? `${d.hours.toFixed(1)} h` : "—"}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="border-t border-line bg-canvas font-semibold">
                <td className="px-5 py-3" colSpan={4}>Total semana</td>
                <td className="px-5 py-3 text-right text-primary">{total.toFixed(1)} h</td>
              </tr>
            </tfoot>
          </table>
        </Card>
      )}
    </div>
  );
}

const fmt = (min: number) => `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;
