"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { useConfirm } from "@/lib/confirm";
import { formatDate } from "@/lib/utils";
import {
  Badge,
  Button,
  Card,
  EmptyState,
  Field,
  Modal,
  Skeleton,
  TextField,
} from "@/components/ui";
import { euro, type Account, type Entry, type FlatPaginated } from "./shared";

export default function AsientosTab({ year, accounts }: { year: number; accounts: Account[] }) {
  const toast = useToast();
  const confirm = useConfirm();
  const [page, setPage] = useState(1);
  const [result, setResult] = useState<FlatPaginated<Entry> | null>(null);
  const [reloadKey, setReloadKey] = useState(0);
  const [expanded, setExpanded] = useState<number | null>(null);
  const [creating, setCreating] = useState(false);

  const reload = useCallback(() => setReloadKey((k) => k + 1), []);

  // Al cambiar de año volvemos a la primera página dentro del propio fetch (sin
  // setState síncrono en el efecto, que la regla react-hooks/set-state-in-effect prohíbe).
  const queriedYear = useRef(year);

  useEffect(() => {
    let active = true;
    const yearChanged = queriedYear.current !== year;
    queriedYear.current = year;
    const effectivePage = yearChanged ? 1 : page;
    void (async () => {
      try {
        const res = await api<FlatPaginated<Entry>>(`/accounting/journal-entries?year=${year}&page=${effectivePage}`);
        if (!active) return;
        if (yearChanged && page !== 1) setPage(1);
        setResult(res);
      } catch {
        if (active) setResult({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 });
      }
    })();
    return () => {
      active = false;
    };
  }, [year, page, reloadKey]);

  async function remove(entry: Entry) {
    const ok = await confirm({
      title: "Eliminar asiento",
      message: `¿Eliminar el asiento del ${formatDate(entry.date)} «${entry.description}»?`,
    });
    if (!ok) return;
    try {
      await api(`/accounting/journal-entries/${entry.id}`, { method: "DELETE" });
      toast.success("Asiento eliminado.");
      reload();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo eliminar el asiento.");
    }
  }

  if (result === null) return <Skeleton rows={6} />;

  return (
    <div className="space-y-4">
      <div className="flex justify-between gap-3">
        <p className="text-sm text-ink-soft">Asientos del ejercicio {year}.</p>
        <Button onClick={() => setCreating(true)}>Nuevo asiento</Button>
      </div>

      {result.data.length === 0 ? (
        <EmptyState title="Sin asientos" message={`No hay asientos registrados en ${year}.`} />
      ) : (
        <Card className="overflow-hidden">
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas/60 text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr>
                <th className="px-4 py-2.5 font-medium">Fecha</th>
                <th className="px-4 py-2.5 font-medium">Descripción</th>
                <th className="px-4 py-2.5 text-right font-medium">Importe</th>
                <th className="px-4 py-2.5 text-center font-medium">Líneas</th>
                <th className="px-4 py-2.5" />
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {result.data.map((entry) => (
                <FragmentRow
                  key={entry.id}
                  entry={entry}
                  expanded={expanded === entry.id}
                  onToggle={() => setExpanded((id) => (id === entry.id ? null : entry.id))}
                  onDelete={() => void remove(entry)}
                />
              ))}
            </tbody>
          </table>
        </Card>
      )}

      {result.last_page > 1 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-ink-soft">
            Página {result.current_page} de {result.last_page} · {result.total} asientos
          </span>
          <div className="flex gap-2">
            <Button variant="ghost" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              Anterior
            </Button>
            <Button variant="ghost" disabled={page >= result.last_page} onClick={() => setPage((p) => p + 1)}>
              Siguiente
            </Button>
          </div>
        </div>
      )}

      {creating && (
        <NewEntryModal
          year={year}
          accounts={accounts}
          onClose={() => setCreating(false)}
          onDone={() => {
            setCreating(false);
            reload();
          }}
        />
      )}
    </div>
  );
}

function FragmentRow({
  entry,
  expanded,
  onToggle,
  onDelete,
}: {
  entry: Entry;
  expanded: boolean;
  onToggle: () => void;
  onDelete: () => void;
}) {
  return (
    <>
      <tr className="cursor-pointer hover:bg-canvas/60" onClick={onToggle}>
        <td className="px-4 py-2.5 whitespace-nowrap">{formatDate(entry.date)}</td>
        <td className="px-4 py-2.5">
          {entry.description}
          {entry.reference && <span className="ml-2 text-xs text-ink-soft">({entry.reference})</span>}
        </td>
        <td className="px-4 py-2.5 text-right font-medium">{euro(entry.total)}</td>
        <td className="px-4 py-2.5 text-center text-ink-soft">{entry.lines.length}</td>
        <td className="px-4 py-2.5 text-right">
          <Button
            variant="ghost"
            onClick={(e) => {
              e.stopPropagation();
              onDelete();
            }}
          >
            Eliminar
          </Button>
        </td>
      </tr>
      {expanded && (
        <tr className="bg-canvas/40">
          <td colSpan={5} className="px-4 py-3">
            <table className="w-full text-sm">
              <thead className="text-left text-xs uppercase tracking-wide text-ink-soft">
                <tr>
                  <th className="py-1 font-medium">Cuenta</th>
                  <th className="py-1 text-right font-medium">Debe</th>
                  <th className="py-1 text-right font-medium">Haber</th>
                </tr>
              </thead>
              <tbody>
                {entry.lines.map((line) => (
                  <tr key={line.id}>
                    <td className="py-1">
                      <span className="font-mono text-primary">{line.account.code}</span>{" "}
                      {line.account.name}
                      {line.description && <span className="ml-2 text-xs text-ink-soft">— {line.description}</span>}
                    </td>
                    <td className="py-1 text-right">{line.debit ? euro(line.debit) : "—"}</td>
                    <td className="py-1 text-right">{line.credit ? euro(line.credit) : "—"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </td>
        </tr>
      )}
    </>
  );
}

type DraftLine = { account_id: string; debit: string; credit: string; description: string };

function emptyLine(): DraftLine {
  return { account_id: "", debit: "", credit: "", description: "" };
}

function NewEntryModal({
  year,
  accounts,
  onClose,
  onDone,
}: {
  year: number;
  accounts: Account[];
  onClose: () => void;
  onDone: () => void;
}) {
  const toast = useToast();
  const [date, setDate] = useState(`${year}-01-01`);
  const [description, setDescription] = useState("");
  const [reference, setReference] = useState("");
  const [lines, setLines] = useState<DraftLine[]>([emptyLine(), emptyLine()]);
  const [saving, setSaving] = useState(false);

  const accountOptions = useMemo<ReadonlyArray<readonly [string, string]>>(
    () => [
      ["", "Selecciona cuenta…"],
      ...accounts
        .slice()
        .sort((a, b) => a.code.localeCompare(b.code, "es", { numeric: true }))
        .map((a) => [String(a.id), `${a.code} · ${a.name}`] as const),
    ],
    [accounts],
  );

  const totals = useMemo(() => {
    let debit = 0;
    let credit = 0;
    for (const l of lines) {
      debit += Number(l.debit) || 0;
      credit += Number(l.credit) || 0;
    }
    const diff = Math.round((debit - credit) * 100) / 100;
    return { debit, credit, diff, balanced: diff === 0 && debit > 0 };
  }, [lines]);

  function updateLine(idx: number, patch: Partial<DraftLine>) {
    setLines((ls) => ls.map((l, i) => (i === idx ? { ...l, ...patch } : l)));
  }

  function addLine() {
    setLines((ls) => [...ls, emptyLine()]);
  }

  function removeLine(idx: number) {
    setLines((ls) => (ls.length <= 2 ? ls : ls.filter((_, i) => i !== idx)));
  }

  async function submit() {
    if (!description.trim()) {
      toast.warning("Indica la descripción del asiento.");
      return;
    }
    const payloadLines = lines
      .filter((l) => l.account_id && ((Number(l.debit) || 0) > 0 || (Number(l.credit) || 0) > 0))
      .map((l) => ({
        account_id: Number(l.account_id),
        debit: Number(l.debit) || 0,
        credit: Number(l.credit) || 0,
        description: l.description.trim() || undefined,
      }));
    if (payloadLines.length < 2) {
      toast.warning("El asiento debe tener al menos 2 líneas con importe.");
      return;
    }
    setSaving(true);
    try {
      await api("/accounting/journal-entries", {
        method: "POST",
        body: {
          date,
          description: description.trim(),
          reference: reference.trim() || undefined,
          lines: payloadLines,
        },
      });
      toast.success("Asiento registrado.");
      onDone();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo registrar el asiento.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal title="Nuevo asiento" onClose={onClose}>
      <div className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <TextField label="Fecha" type="date" value={date} onChange={setDate} />
          <div className="sm:col-span-2">
            <TextField label="Referencia (opcional)" value={reference} onChange={setReference} placeholder="Ej. FAC-2024-001" />
          </div>
        </div>
        <TextField label="Descripción" value={description} onChange={setDescription} placeholder="Concepto del asiento" />

        <Field label="Líneas">
          <div className="space-y-2">
            <div className="hidden gap-2 px-1 text-xs uppercase tracking-wide text-ink-soft sm:grid sm:grid-cols-[1fr_7rem_7rem_1fr_2rem]">
              <span>Cuenta</span>
              <span className="text-right">Debe</span>
              <span className="text-right">Haber</span>
              <span>Concepto</span>
              <span />
            </div>
            {lines.map((line, idx) => (
              <div key={idx} className="grid gap-2 sm:grid-cols-[1fr_7rem_7rem_1fr_2rem] sm:items-center">
                <select
                  value={line.account_id}
                  onChange={(e) => updateLine(idx, { account_id: e.target.value })}
                  className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                >
                  {accountOptions.map(([v, l]) => (
                    <option key={v} value={v}>{l}</option>
                  ))}
                </select>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  value={line.debit}
                  onChange={(e) => updateLine(idx, { debit: e.target.value, credit: "" })}
                  placeholder="Debe"
                  className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-right text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                />
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  value={line.credit}
                  onChange={(e) => updateLine(idx, { credit: e.target.value, debit: "" })}
                  placeholder="Haber"
                  className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-right text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                />
                <input
                  type="text"
                  value={line.description}
                  onChange={(e) => updateLine(idx, { description: e.target.value })}
                  placeholder="Concepto"
                  className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                />
                <button
                  type="button"
                  onClick={() => removeLine(idx)}
                  disabled={lines.length <= 2}
                  className="text-ink-soft hover:text-ink disabled:opacity-30"
                  aria-label="Eliminar línea"
                  title="Eliminar línea"
                >
                  ✕
                </button>
              </div>
            ))}
          </div>
          <div className="mt-3">
            <Button variant="secondary" onClick={addLine}>+ Añadir línea</Button>
          </div>
        </Field>

        <div className="flex flex-wrap items-center justify-between gap-3 rounded-[var(--radius-fluent)] border border-line bg-canvas/60 px-4 py-3 text-sm">
          <div className="flex gap-6">
            <span>Debe: <strong>{euro(totals.debit)}</strong></span>
            <span>Haber: <strong>{euro(totals.credit)}</strong></span>
          </div>
          {totals.balanced ? (
            <Badge tone="ok">Cuadra</Badge>
          ) : (
            <span className="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
              {totals.debit === 0 ? "Sin importes" : `Descuadra (diferencia ${euro(totals.diff)})`}
            </span>
          )}
        </div>

        <div className="flex justify-end gap-2">
          <Button variant="ghost" onClick={onClose}>Cancelar</Button>
          <Button onClick={submit} disabled={saving || !totals.balanced}>
            {saving ? "Guardando…" : "Guardar"}
          </Button>
        </div>
      </div>
    </Modal>
  );
}
