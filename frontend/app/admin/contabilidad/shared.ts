// Tipos y utilidades compartidas del módulo de contabilidad.

export type AccountType = "activo" | "pasivo" | "patrimonio" | "ingreso" | "gasto";

export type Account = {
  id: number;
  code: string;
  name: string;
  type: AccountType;
  parent_id: number | null;
  active: boolean;
};

export type EntryLine = {
  id: number;
  account_id: number;
  debit: number;
  credit: number;
  description: string | null;
  account: { code: string; name: string };
};

export type Entry = {
  id: number;
  date: string;
  description: string;
  reference: string | null;
  total: number;
  lines: EntryLine[];
};

/** Paginador plano de Laravel (no anidado en `meta`). */
export type FlatPaginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
};

export type LedgerRow = {
  date: string;
  description: string;
  debit: number;
  credit: number;
  balance: number;
};

export type ReportAccount = { code: string; name: string; amount: number };

export type BalanceSheet = {
  activo: { accounts: ReportAccount[]; total: number };
  pasivo: { accounts: ReportAccount[]; total: number };
  patrimonio: { accounts: ReportAccount[]; resultado_ejercicio: number; total: number };
  total_activo: number;
  total_pasivo_patrimonio: number;
  balanced: boolean;
};

export type IncomeStatement = {
  ingresos: { accounts: ReportAccount[]; total: number };
  gastos: { accounts: ReportAccount[]; total: number };
  resultado: number;
};

export type FiscalPeriod = {
  id: number;
  year: number;
  status: "open" | "closed";
  closed_at: string | null;
};

export const ACCOUNT_TYPES: ReadonlyArray<readonly [AccountType, string]> = [
  ["activo", "Activo"],
  ["pasivo", "Pasivo"],
  ["patrimonio", "Patrimonio neto"],
  ["ingreso", "Ingresos"],
  ["gasto", "Gastos"],
];

const TYPE_LABEL: Record<AccountType, string> = {
  activo: "Activo",
  pasivo: "Pasivo",
  patrimonio: "Patrimonio neto",
  ingreso: "Ingresos",
  gasto: "Gastos",
};

export function accountTypeLabel(type: AccountType): string {
  return TYPE_LABEL[type] ?? type;
}

const EUR = new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" });

/** Importe formateado en euros (es-ES). */
export function euro(value: number): string {
  return EUR.format(Number.isFinite(value) ? value : 0);
}

/** Lista de años para el selector (current-2 … current+1). */
export function yearOptions(current: number): ReadonlyArray<readonly [string, string]> {
  const years: Array<readonly [string, string]> = [];
  for (let y = current - 2; y <= current + 1; y++) {
    years.push([String(y), String(y)] as const);
  }
  return years;
}
