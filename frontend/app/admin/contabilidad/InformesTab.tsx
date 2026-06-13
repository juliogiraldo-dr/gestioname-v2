"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Badge, Button, Card, EmptyState, Skeleton } from "@/components/ui";
import { euro, type BalanceSheet, type IncomeStatement, type ReportAccount } from "./shared";

export default function InformesTab({ year }: { year: number }) {
  const [balance, setBalance] = useState<BalanceSheet | null>(null);
  const [income, setIncome] = useState<IncomeStatement | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    let active = true;
    void (async () => {
      setLoading(true);
      setError(false);
      try {
        const [bs, is] = await Promise.all([
          api<{ data: BalanceSheet }>(`/accounting/balance-sheet?year=${year}`),
          api<{ data: IncomeStatement }>(`/accounting/income-statement?year=${year}`),
        ]);
        if (active) {
          setBalance(bs.data);
          setIncome(is.data);
        }
      } catch {
        if (active) setError(true);
      } finally {
        if (active) setLoading(false);
      }
    })();
    return () => {
      active = false;
    };
  }, [year]);

  if (loading) return <Skeleton rows={8} />;
  if (error || !balance || !income) {
    return <EmptyState title="Sin datos" message={`No se pudieron cargar los informes del ejercicio ${year}.`} />;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-end print:hidden">
        <Button variant="secondary" onClick={() => window.print()}>Exportar PDF</Button>
      </div>

      <section className="space-y-3">
        <div className="flex items-center justify-between">
          <h2 className="text-lg font-semibold text-primary">Balance de situación · {year}</h2>
          {balance.balanced ? <Badge tone="ok">Cuadra</Badge> : <Badge tone="warn">No cuadra</Badge>}
        </div>
        <div className="grid gap-4 lg:grid-cols-2">
          <ReportBlock title="Activo" accounts={balance.activo.accounts} total={balance.activo.total} totalLabel="Total activo" />
          <Card className="overflow-hidden">
            <BlockTable title="Pasivo" accounts={balance.pasivo.accounts} />
            <BlockTable
              title="Patrimonio neto"
              accounts={balance.patrimonio.accounts}
              extraRow={["Resultado del ejercicio", balance.patrimonio.resultado_ejercicio]}
            />
            <div className="flex items-center justify-between border-t border-line bg-canvas/60 px-4 py-2.5 text-sm font-semibold">
              <span>Total pasivo + patrimonio</span>
              <span>{euro(balance.total_pasivo_patrimonio)}</span>
            </div>
          </Card>
        </div>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold text-primary">Cuenta de resultados · {year}</h2>
        <div className="grid gap-4 lg:grid-cols-2">
          <ReportBlock title="Ingresos" accounts={income.ingresos.accounts} total={income.ingresos.total} totalLabel="Total ingresos" />
          <ReportBlock title="Gastos" accounts={income.gastos.accounts} total={income.gastos.total} totalLabel="Total gastos" />
        </div>
        <Card className="flex items-center justify-between px-4 py-3 text-sm font-semibold">
          <span>Resultado del ejercicio</span>
          <span className={income.resultado >= 0 ? "text-[#0d6b50]" : "text-red-700"}>{euro(income.resultado)}</span>
        </Card>
      </section>
    </div>
  );
}

function ReportBlock({
  title,
  accounts,
  total,
  totalLabel,
}: {
  title: string;
  accounts: ReportAccount[];
  total: number;
  totalLabel: string;
}) {
  return (
    <Card className="overflow-hidden">
      <BlockTable title={title} accounts={accounts} />
      <div className="flex items-center justify-between border-t border-line bg-canvas/60 px-4 py-2.5 text-sm font-semibold">
        <span>{totalLabel}</span>
        <span>{euro(total)}</span>
      </div>
    </Card>
  );
}

function BlockTable({
  title,
  accounts,
  extraRow,
}: {
  title: string;
  accounts: ReportAccount[];
  extraRow?: readonly [string, number];
}) {
  return (
    <div>
      <h3 className="border-b border-line px-4 py-2 text-xs font-semibold uppercase tracking-wide text-ink-soft">{title}</h3>
      <table className="w-full text-sm">
        <tbody className="divide-y divide-line">
          {accounts.length === 0 && !extraRow && (
            <tr>
              <td className="px-4 py-2.5 text-ink-soft" colSpan={2}>Sin importes</td>
            </tr>
          )}
          {accounts.map((a) => (
            <tr key={a.code}>
              <td className="px-4 py-2">
                <span className="font-mono text-primary">{a.code}</span> {a.name}
              </td>
              <td className="px-4 py-2 text-right">{euro(a.amount)}</td>
            </tr>
          ))}
          {extraRow && (
            <tr>
              <td className="px-4 py-2 italic text-ink-soft">{extraRow[0]}</td>
              <td className="px-4 py-2 text-right">{euro(extraRow[1])}</td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
