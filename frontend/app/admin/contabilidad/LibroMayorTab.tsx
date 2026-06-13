"use client";

import { useEffect, useMemo, useState } from "react";
import { api } from "@/lib/api";
import { formatDate } from "@/lib/utils";
import { Card, EmptyState, SelectField, Skeleton, StatCard } from "@/components/ui";
import { euro, type Account, type LedgerRow } from "./shared";

export default function LibroMayorTab({ year, accounts }: { year: number; accounts: Account[] }) {
  const [accountId, setAccountId] = useState("");
  const [rows, setRows] = useState<LedgerRow[] | null>(null);
  const [loading, setLoading] = useState(false);

  const options = useMemo<ReadonlyArray<readonly [string, string]>>(
    () => [
      ["", "Selecciona una cuenta…"],
      ...accounts
        .slice()
        .sort((a, b) => a.code.localeCompare(b.code, "es", { numeric: true }))
        .map((a) => [String(a.id), `${a.code} · ${a.name}`] as const),
    ],
    [accounts],
  );

  useEffect(() => {
    let active = true;
    void (async () => {
      if (!accountId) {
        setRows(null);
        return;
      }
      setLoading(true);
      try {
        const res = await api<{ data: LedgerRow[] }>(`/accounting/ledger?account_id=${accountId}&year=${year}`);
        if (active) setRows(res.data);
      } catch {
        if (active) setRows([]);
      } finally {
        if (active) setLoading(false);
      }
    })();
    return () => {
      active = false;
    };
  }, [accountId, year]);

  const finalBalance = rows && rows.length > 0 ? rows[rows.length - 1].balance : 0;

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-end gap-4">
        <SelectField
          label="Cuenta"
          value={accountId}
          onChange={setAccountId}
          options={options}
          className="max-w-md flex-1"
        />
        {rows && rows.length > 0 && (
          <div className="min-w-[12rem]">
            <StatCard label={`Saldo final ${year}`} value={euro(finalBalance)} />
          </div>
        )}
      </div>

      {!accountId ? (
        <EmptyState title="Libro mayor" message="Selecciona una cuenta para ver sus movimientos." />
      ) : loading || rows === null ? (
        <Skeleton rows={6} />
      ) : rows.length === 0 ? (
        <EmptyState title="Sin movimientos" message={`La cuenta no tiene movimientos en ${year}.`} />
      ) : (
        <Card className="overflow-hidden">
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas/60 text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr>
                <th className="px-4 py-2.5 font-medium">Fecha</th>
                <th className="px-4 py-2.5 font-medium">Descripción</th>
                <th className="px-4 py-2.5 text-right font-medium">Debe</th>
                <th className="px-4 py-2.5 text-right font-medium">Haber</th>
                <th className="px-4 py-2.5 text-right font-medium">Saldo</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {rows.map((r, i) => (
                <tr key={i} className="hover:bg-canvas/60">
                  <td className="px-4 py-2.5 whitespace-nowrap">{formatDate(r.date)}</td>
                  <td className="px-4 py-2.5">{r.description}</td>
                  <td className="px-4 py-2.5 text-right">{r.debit ? euro(r.debit) : "—"}</td>
                  <td className="px-4 py-2.5 text-right">{r.credit ? euro(r.credit) : "—"}</td>
                  <td className="px-4 py-2.5 text-right font-medium">{euro(r.balance)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  );
}
