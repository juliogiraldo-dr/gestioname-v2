"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError, downloadFile } from "@/lib/api";
import { Badge, Button, Card, EmptyState, Modal, PageHeader, SelectField, Spinner, TextField } from "@/components/ui";

type Entity = {
  id: string;
  name: string;
  type: string;
  cif: string | null;
  opening_balance: number;
  fiscal_year: number | null;
  members_count?: number;
};
type MemberType = { id: string; name: string; fee_amount: number; fee_periodicity: string };

const ENTITY_TYPES = [
  ["pena", "Peña"],
  ["ampa", "AMPA"],
  ["asociacion_cultural", "Asociación cultural"],
  ["vecinal", "Vecinal"],
  ["club", "Club"],
  ["cofradia", "Cofradía"],
  ["otro", "Otro"],
] as const;

const PERIODICITIES = [
  ["anual", "Anual"],
  ["semestral", "Semestral"],
  ["trimestral", "Trimestral"],
  ["mensual", "Mensual"],
] as const;

const typeLabel = (t: string) => ENTITY_TYPES.find(([v]) => v === t)?.[1] ?? t;
const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

export default function EntidadesPage() {
  const [entities, setEntities] = useState<Entity[] | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<Entity | null>(null);

  const load = useCallback(async () => {
    const res = await api<{ data: Entity[] }>("/entities");
    setEntities(res.data);
  }, []);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  if (!entities) return <Spinner />;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Entidades"
        subtitle="Asociaciones, tipos de socio y cuotas"
        action={<Button onClick={() => { setEditing(null); setShowForm(true); }}>Nueva entidad</Button>}
      />

      {showForm && (
        <EntityForm entity={editing} onClose={() => setShowForm(false)} onSaved={() => { setShowForm(false); void load(); }} />
      )}

      {entities.length === 0 ? (
        <EmptyState title="Sin entidades" message="Crea tu primera entidad (peña, AMPA, asociación, club…) con «Nueva entidad»." />
      ) : (
        <div className="space-y-4">
          {entities.map((e) => (
            <EntityCard key={e.id} entity={e} onEdit={() => { setEditing(e); setShowForm(true); }} onChanged={load} />
          ))}
        </div>
      )}
    </div>
  );
}

function EntityCard({ entity, onEdit, onChanged }: { entity: Entity; onEdit: () => void; onChanged: () => Promise<void> }) {
  const [showTypes, setShowTypes] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function remove() {
    setError(null);
    try {
      await api(`/entities/${entity.id}`, { method: "DELETE" });
      await onChanged();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "No se pudo eliminar");
    }
  }

  return (
    <Card className="p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="font-semibold text-ink">{entity.name}</h3>
            <Badge tone="info">{typeLabel(entity.type)}</Badge>
          </div>
          <p className="mt-1 text-sm text-ink-soft">
            {entity.cif ?? "Sin CIF"} · Saldo inicial {eur(entity.opening_balance)} · Ejercicio {entity.fiscal_year ?? "—"}
            {typeof entity.members_count === "number" && ` · ${entity.members_count} socios`}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="ghost" onClick={() => setShowTypes((v) => !v)}>Tipos de socio</Button>
          <Button variant="ghost" onClick={() => void downloadFile(`/entities/${entity.id}/backup`, { method: "GET", fallbackName: `backup-${entity.name}.json` })}>Backup</Button>
          <Button variant="ghost" onClick={onEdit}>Editar</Button>
          <button onClick={remove} className="text-xs font-medium text-red-600 hover:underline">Eliminar</button>
        </div>
      </div>
      {error && <p className="mt-2 text-sm text-red-700">{error}</p>}
      {showTypes && <MemberTypesPanel entityId={entity.id} />}
    </Card>
  );
}

function MemberTypesPanel({ entityId }: { entityId: string }) {
  const [types, setTypes] = useState<MemberType[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ name: "", fee_amount: "", fee_periodicity: "anual" });
  const [editingId, setEditingId] = useState<string | null>(null);

  const load = useCallback(async () => {
    const res = await api<{ data: MemberType[] }>(`/entities/${entityId}/member-types`);
    setTypes(res.data);
    setLoading(false);
  }, [entityId]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function save() {
    const body = { name: form.name, fee_amount: Number(form.fee_amount || 0), fee_periodicity: form.fee_periodicity };
    if (editingId) await api(`/member-types/${editingId}`, { method: "PUT", body });
    else await api(`/entities/${entityId}/member-types`, { method: "POST", body });
    setForm({ name: "", fee_amount: "", fee_periodicity: "anual" });
    setEditingId(null);
    await load();
  }

  function edit(t: MemberType) {
    setEditingId(t.id);
    setForm({ name: t.name, fee_amount: String(t.fee_amount), fee_periodicity: t.fee_periodicity });
  }

  async function remove(id: string) {
    try { await api(`/member-types/${id}`, { method: "DELETE" }); await load(); } catch { /* tipo en uso */ }
  }

  return (
    <div className="mt-4 rounded-[var(--radius-fluent)] border border-line bg-canvas/60 p-4">
      <h4 className="mb-3 text-sm font-semibold text-primary">Tipos de socio y cuotas</h4>
      {loading ? <Spinner /> : (
        <div className="mb-4 space-y-1">
          {types.length === 0 && <p className="text-sm text-ink-soft">Sin tipos definidos.</p>}
          {types.map((t) => (
            <div key={t.id} className="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-line/40">
              <span className="text-ink">{t.name} — <span className="font-medium">{eur(t.fee_amount)}</span><span className="text-ink-soft"> / {t.fee_periodicity}</span></span>
              <span className="flex gap-3">
                <button onClick={() => edit(t)} className="text-xs text-primary hover:underline">Editar</button>
                <button onClick={() => remove(t.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
              </span>
            </div>
          ))}
        </div>
      )}
      <div className="flex flex-wrap items-end gap-2">
        <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} className="w-40" />
        <TextField label="Cuota (€)" type="number" value={form.fee_amount} onChange={(v) => setForm((p) => ({ ...p, fee_amount: v }))} className="w-28" />
        <SelectField label="Periodicidad" value={form.fee_periodicity} onChange={(v) => setForm((p) => ({ ...p, fee_periodicity: v }))} options={PERIODICITIES} />
        <Button variant="secondary" onClick={save} disabled={!form.name}>{editingId ? "Guardar" : "Añadir tipo"}</Button>
        {editingId && <Button variant="ghost" onClick={() => { setEditingId(null); setForm({ name: "", fee_amount: "", fee_periodicity: "anual" }); }}>Cancelar</Button>}
      </div>
    </div>
  );
}

function EntityForm({ entity, onClose, onSaved }: { entity: Entity | null; onClose: () => void; onSaved: () => void }) {
  const [form, setForm] = useState(
    entity
      ? { name: entity.name, type: entity.type, cif: entity.cif ?? "", opening_balance: String(entity.opening_balance), fiscal_year: entity.fiscal_year ? String(entity.fiscal_year) : "" }
      : { name: "", type: "pena", cif: "", opening_balance: "0", fiscal_year: String(new Date().getFullYear()) },
  );
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  async function submit() {
    setError(null); setBusy(true);
    try {
      const body = { name: form.name, type: form.type, cif: form.cif || null, opening_balance: Number(form.opening_balance || 0), fiscal_year: form.fiscal_year ? Number(form.fiscal_year) : null };
      await api(entity ? `/entities/${entity.id}` : "/entities", { method: entity ? "PUT" : "POST", body });
      onSaved();
    } catch (err) { setError(err instanceof ApiError ? err.message : "No se pudo guardar"); } finally { setBusy(false); }
  }

  return (
    <Modal title={entity ? "Editar entidad" : "Nueva entidad"} onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <TextField label="Nombre" value={form.name} onChange={(v) => set("name", v)} />
        <SelectField label="Tipo" value={form.type} onChange={(v) => set("type", v)} options={ENTITY_TYPES} />
        <TextField label="CIF" value={form.cif} onChange={(v) => set("cif", v)} />
        <TextField label="Saldo inicial (€)" type="number" value={form.opening_balance} onChange={(v) => set("opening_balance", v)} />
        <TextField label="Ejercicio" type="number" value={form.fiscal_year} onChange={(v) => set("fiscal_year", v)} />
      </div>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.name}>{busy ? "Guardando…" : "Guardar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}
