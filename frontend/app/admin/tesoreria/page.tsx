"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { api, ApiError } from "@/lib/api";
import { formatDate } from "@/lib/utils";
import { Badge, Button, Card, EmptyState, Modal, PageHeader, SelectField, Spinner, StatCard, TextField } from "@/components/ui";
import { DateInput } from "@/components/DateInput";

type Entity = { id: string; name: string };
type Category = { id: string; name: string };
type Treasury = { year: number; opening_balance: number; income: number; pending: number; expenses: number; balance: number };
type Expense = { id: string; amount: number; date: string; description: string; category_id: string | null; category?: { name: string } | null };
type Payment = { id: string; year: number; amount: number; status: string; payment_method: string | null; member?: { full_name: string } | null };

const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

export default function TesoreriaPage() {
  const [entities, setEntities] = useState<Entity[] | null>(null);
  const [entityId, setEntityId] = useState("");
  const [year, setYear] = useState(new Date().getFullYear());
  const [treasury, setTreasury] = useState<Treasury | null>(null);
  const [expenses, setExpenses] = useState<Expense[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [payments, setPayments] = useState<Payment[]>([]);
  const [editing, setEditing] = useState<Expense | null>(null);
  const [showForm, setShowForm] = useState(false);

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: Entity[] }>("/entities");
      setEntities(res.data);
      if (res.data[0]) setEntityId(res.data[0].id);
    })();
  }, []);

  const load = useCallback(async () => {
    if (!entityId) return;
    const [t, e, c, p] = await Promise.all([
      api<{ data: Treasury }>(`/entities/${entityId}/treasury/${year}`),
      api<{ data: Expense[] }>(`/entities/${entityId}/expenses`),
      api<{ data: Category[] }>(`/entities/${entityId}/expense-categories`),
      api<{ data: Payment[] }>(`/entities/${entityId}/payments?year=${year}`),
    ]);
    setTreasury(t.data);
    setExpenses(e.data);
    setCategories(c.data);
    setPayments(p.data);
  }, [entityId, year]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function removeExpense(id: string) { await api(`/expenses/${id}`, { method: "DELETE" }); await load(); }

  if (!entities) return <Spinner />;
  if (entities.length === 0) {
    return (
      <div>
        <PageHeader title="Tesorería" subtitle="Ingresos, gastos y saldo por entidad" />
        <EmptyState title="Sin entidades" message="La tesorería pertenece a una entidad. Crea una entidad para empezar."
          action={<Link href="/admin/entidades"><Button>Ir a Entidades</Button></Link>} />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Tesorería" subtitle="Ingresos, gastos y saldo por entidad" />

      <Card className="p-5">
        <div className="flex flex-wrap items-end gap-3">
          <SelectField label="Entidad" value={entityId} onChange={setEntityId} options={entities.map((e) => [e.id, e.name] as const)} />
          <TextField label="Ejercicio" type="number" value={String(year)} onChange={(v) => setYear(Number(v))} className="w-28" />
        </div>
      </Card>

      {!treasury ? <Spinner /> : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard label="Saldo inicial" value={eur(treasury.opening_balance)} />
            <StatCard label="Ingresos cobrados" value={eur(treasury.income)} />
            <StatCard label="Pendiente de cobro" value={eur(treasury.pending)} />
            <StatCard label="Gastos" value={eur(treasury.expenses)} />
            <StatCard label="Saldo banco" value={eur(treasury.balance)} hint={`Ejercicio ${treasury.year}`} />
          </div>

          {showForm && (
            <ExpenseForm entityId={entityId} categories={categories} expense={editing}
              onClose={() => setShowForm(false)} onSaved={() => { setShowForm(false); void load(); }} />
          )}

          <Card className="overflow-hidden">
            <div className="flex items-center justify-between border-b border-line px-5 py-3">
              <h2 className="text-sm font-semibold text-primary">Gastos</h2>
              <Button variant="secondary" onClick={() => { setEditing(null); setShowForm(true); }}>Nuevo gasto</Button>
            </div>
            {expenses.length === 0 ? <p className="p-6 text-sm text-ink-soft">No hay gastos registrados.</p> : (
              <table className="w-full text-sm">
                <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
                  <tr><th className="px-5 py-3 font-medium">Fecha</th><th className="px-5 py-3 font-medium">Descripción</th><th className="px-5 py-3 font-medium">Categoría</th><th className="px-5 py-3 text-right font-medium">Importe</th><th className="px-5 py-3"></th></tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {expenses.map((x) => (
                    <tr key={x.id}>
                      <td className="px-5 py-3 text-ink-soft">{formatDate(x.date)}</td>
                      <td className="px-5 py-3 text-ink">{x.description}</td>
                      <td className="px-5 py-3 text-ink-soft">{x.category?.name ?? "—"}</td>
                      <td className="px-5 py-3 text-right font-medium text-ink">{eur(x.amount)}</td>
                      <td className="px-5 py-3 text-right">
                        <button onClick={() => { setEditing(x); setShowForm(true); }} className="mr-3 text-xs font-medium text-primary hover:underline">Editar</button>
                        <button onClick={() => removeExpense(x.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </Card>

          <Card className="overflow-hidden">
            <div className="border-b border-line px-5 py-3"><h2 className="text-sm font-semibold text-primary">Pagos de cuotas {year}</h2></div>
            {payments.length === 0 ? <p className="p-6 text-sm text-ink-soft">No hay pagos en este ejercicio.</p> : (
              <table className="w-full text-sm">
                <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
                  <tr><th className="px-5 py-3 font-medium">Socio</th><th className="px-5 py-3 text-right font-medium">Importe</th><th className="px-5 py-3 font-medium">Estado</th><th className="px-5 py-3 font-medium">Método</th></tr>
                </thead>
                <tbody className="divide-y divide-line">
                  {payments.map((p) => (
                    <tr key={p.id}>
                      <td className="px-5 py-3 text-ink">{p.member?.full_name ?? "—"}</td>
                      <td className="px-5 py-3 text-right font-medium text-ink">{eur(p.amount)}</td>
                      <td className="px-5 py-3"><Badge tone={p.status === "pagado" ? "ok" : p.status === "parcial" ? "info" : "warn"}>{p.status}</Badge></td>
                      <td className="px-5 py-3 text-ink-soft">{p.payment_method ?? "—"}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </Card>
        </>
      )}
    </div>
  );
}

function ExpenseForm({ entityId, categories, expense, onClose, onSaved }: {
  entityId: string; categories: Category[]; expense: Expense | null; onClose: () => void; onSaved: () => void;
}) {
  const today = new Date().toISOString().slice(0, 10);
  const [form, setForm] = useState({
    description: expense?.description ?? "", amount: expense ? String(expense.amount) : "",
    date: expense?.date ?? today, category_id: expense?.category_id ?? "",
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  async function submit() {
    setError(null); setBusy(true);
    try {
      const body = { description: form.description, amount: Number(form.amount), date: form.date, category_id: form.category_id || null };
      await api(expense ? `/expenses/${expense.id}` : `/entities/${entityId}/expenses`, { method: expense ? "PUT" : "POST", body });
      onSaved();
    } catch (err) { setError(err instanceof ApiError ? err.message : "No se pudo guardar el gasto"); } finally { setBusy(false); }
  }

  return (
    <Modal title={expense ? "Editar gasto" : "Nuevo gasto"} onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <TextField label="Descripción" value={form.description} onChange={(v) => set("description", v)} className="sm:col-span-2" />
        <TextField label="Importe (€)" type="number" value={form.amount} onChange={(v) => set("amount", v)} />
        <DateInput label="Fecha" value={form.date} onChange={(v) => set("date", v)} />
        <SelectField label="Categoría" value={form.category_id} onChange={(v) => set("category_id", v)}
          options={[["", "Sin categoría"], ...categories.map((c) => [c.id, c.name] as const)]} />
      </div>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.description || !form.amount}>{busy ? "Guardando…" : "Guardar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}
