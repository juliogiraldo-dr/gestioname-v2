"use client";

import { useState } from "react";
import { Field } from "./ui";

const MESES = [
  "enero", "febrero", "marzo", "abril", "mayo", "junio",
  "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre",
];

const SELECT_CLASS =
  "rounded-[var(--radius-fluent)] border border-line bg-canvas px-2 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30";

type Parts = { d: string; m: string; y: string };

function fromValue(value: string): Parts {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || "");
  return match ? { y: match[1], m: match[2], d: match[3] } : { y: "", m: "", d: "" };
}

/**
 * Selector de fecha en formato español (día / mes / año) que evita la ambigüedad
 * mm/dd del input nativo `type="date"`. Emite siempre `YYYY-MM-DD` (o "" si incompleta).
 * API equivalente a TextField: `label`, `value` (YYYY-MM-DD), `onChange`.
 */
export function DateInput({
  label,
  value,
  onChange,
  className = "",
  minYear,
  maxYear,
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  className?: string;
  minYear?: number;
  maxYear?: number;
}) {
  // Estado interno para conservar selecciones parciales (el valor solo se emite completo).
  const [parts, setParts] = useState<Parts>(() => fromValue(value));

  const now = new Date().getFullYear();
  const max = maxYear ?? now + 5;
  const min = minYear ?? 1940;
  const years: number[] = [];
  for (let y = max; y >= min; y--) years.push(y);

  function update(next: Parts) {
    setParts(next);
    onChange(next.d && next.m && next.y ? `${next.y}-${next.m}-${next.d}` : "");
  }

  return (
    <Field label={label} className={className}>
      <div className="flex gap-2">
        <select aria-label="Día" value={parts.d} onChange={(e) => update({ ...parts, d: e.target.value })} className={SELECT_CLASS}>
          <option value="">Día</option>
          {Array.from({ length: 31 }, (_, i) => String(i + 1).padStart(2, "0")).map((d) => (
            <option key={d} value={d}>{Number(d)}</option>
          ))}
        </select>
        <select aria-label="Mes" value={parts.m} onChange={(e) => update({ ...parts, m: e.target.value })} className={`${SELECT_CLASS} flex-1`}>
          <option value="">Mes</option>
          {MESES.map((name, i) => (
            <option key={name} value={String(i + 1).padStart(2, "0")}>{name}</option>
          ))}
        </select>
        <select aria-label="Año" value={parts.y} onChange={(e) => update({ ...parts, y: e.target.value })} className={SELECT_CLASS}>
          <option value="">Año</option>
          {years.map((y) => (
            <option key={y} value={String(y)}>{y}</option>
          ))}
        </select>
      </div>
    </Field>
  );
}
