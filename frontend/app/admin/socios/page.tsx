"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useRef } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { api, ApiError, downloadFile, uploadFile } from "@/lib/api";
import { useDebounce } from "@/lib/hooks";
import { useToast } from "@/lib/toast";
import { useConfirm } from "@/lib/confirm";
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, Pagination, type Paginated, SelectField, Skeleton, Spinner, TextField } from "@/components/ui";
import { DateInput } from "@/components/DateInput";

type Entity = { id: string; name: string };
type MemberType = { id: string; name: string; fee_amount: number };
type Member = {
  id: string;
  member_number: string | null;
  full_name: string;
  email: string | null;
  phone: string | null;
  status: string;
  member_type?: { name: string } | null;
};

const STATUS_TONES: Record<string, "ok" | "warn" | "neutral" | "info"> = {
  activo: "ok", pendiente: "warn", baja_voluntaria: "neutral", baja_impagada: "warn", honor: "info",
};
const STATUSES = [
  ["activo", "Activo"], ["pendiente", "Pendiente"], ["baja_voluntaria", "Baja voluntaria"],
  ["baja_impagada", "Baja por impago"], ["honor", "De honor"],
] as const;
const STATUS_FILTERS = [["", "Todos"], ...STATUSES] as const;
const PAY_STATUSES = [["pagado", "Pagado"], ["parcial", "Parcial"], ["pendiente", "Pendiente"]] as const;
const PAY_METHODS = [
  ["efectivo", "Efectivo"], ["transferencia", "Transferencia"], ["bizum", "Bizum"],
  ["domiciliacion", "Domiciliación"], ["otro", "Otro"],
] as const;
const eur = (n: number) => new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" }).format(n);

export default function SociosPage() {
  const [entities, setEntities] = useState<Entity[] | null>(null);
  const [entityId, setEntityId] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounce(search, 300);
  const [page, setPage] = useState(1);
  const [showNewMember, setShowNewMember] = useState(false);
  const [selectedMemberId, setSelectedMemberId] = useState<string | null>(null);
  const toast = useToast();
  const confirm = useConfirm();
  const queryClient = useQueryClient();

  useEffect(() => {
    void (async () => {
      const res = await api<{ data: Entity[] }>("/entities");
      setEntities(res.data);
      if (res.data[0]) setEntityId(res.data[0].id);
    })();
  }, []);

  const typesQuery = useQuery({
    queryKey: ["member-types", entityId],
    enabled: !!entityId,
    queryFn: () => api<{ data: MemberType[] }>(`/entities/${entityId}/member-types`),
  });
  const types = typesQuery.data?.data ?? [];

  const membersQuery = useQuery({
    queryKey: ["members", entityId, statusFilter, debouncedSearch, page],
    enabled: !!entityId,
    queryFn: () => {
      const params = new URLSearchParams({ page: String(page) });
      if (statusFilter) params.set("status", statusFilter);
      if (debouncedSearch) params.set("search", debouncedSearch);
      return api<Paginated<Member>>(`/entities/${entityId}/members?${params}`);
    },
  });
  const members = membersQuery.data?.data ?? [];

  const refreshMembers = useCallback(() => { void queryClient.invalidateQueries({ queryKey: ["members"] }); }, [queryClient]);

  const fileRef = useRef<HTMLInputElement>(null);

  async function removeMember(id: string, name: string) {
    const ok = await confirm({ title: "Eliminar socio", message: `¿Seguro que quieres eliminar a ${name}? Esta acción no se puede deshacer.` });
    if (!ok) return;
    try {
      await api(`/members/${id}`, { method: "DELETE" });
      toast.success("Socio eliminado.");
      refreshMembers();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo eliminar.");
    }
  }

  const filterQs = () => {
    const p = new URLSearchParams();
    if (statusFilter) p.set("status", statusFilter);
    return p.toString();
  };

  async function exportExcel() {
    try { await downloadFile(`/entities/${entityId}/members/export?${filterQs()}`, { method: "GET", fallbackName: "socios.xlsx" }); }
    catch { toast.error("No se pudo exportar."); }
  }
  async function listadoPdf() {
    try { await downloadFile(`/entities/${entityId}/members-pdf?${filterQs()}`, { method: "GET", fallbackName: "socios.pdf" }); }
    catch { toast.error("No se pudo generar el PDF."); }
  }
  async function importExcel(file: File) {
    try {
      const res = await uploadFile<{ data: { imported: number; errors: unknown[] } }>(`/entities/${entityId}/members/import`, file);
      toast.success(`${res.data.imported} socios importados${res.data.errors.length ? `, ${res.data.errors.length} con errores` : ""}.`);
      refreshMembers();
    } catch (e) { toast.error(e instanceof ApiError ? e.message : "No se pudo importar."); }
  }

  if (!entities) return <Spinner />;
  if (entities.length === 0) {
    return (
      <div>
        <PageHeader title="Socios" subtitle="Fichas, pagos y estados" />
        <EmptyState
          title="Sin entidades"
          message="Los socios pertenecen a una entidad. Crea una entidad para empezar."
          action={<Link href="/admin/entidades"><Button>Ir a Entidades</Button></Link>}
        />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Socios" subtitle="Fichas, pagos y estados" />

      <Card className="p-5">
        <div className="flex flex-wrap items-end gap-3">
          <SelectField label="Entidad" value={entityId} onChange={(v) => { setEntityId(v); setPage(1); }} options={entities.map((e) => [e.id, e.name] as const)} />
          <SelectField label="Estado" value={statusFilter} onChange={(v) => { setStatusFilter(v); setPage(1); }} options={STATUS_FILTERS} />
          <TextField label="Buscar" value={search} onChange={(v) => { setSearch(v); setPage(1); }} placeholder="Nombre o nº de socio" className="flex-1" />
          <Button variant="secondary" onClick={() => setShowNewMember((v) => !v)}>Nuevo socio</Button>
        </div>
        {entityId && entities.length > 0 && (
          <div className="mt-4 flex flex-wrap gap-2 border-t border-line pt-4 text-sm">
            <Button variant="ghost" onClick={exportExcel}>Exportar Excel</Button>
            <Button variant="ghost" onClick={() => fileRef.current?.click()}>Importar Excel</Button>
            <Button variant="ghost" onClick={listadoPdf}>PDF del listado</Button>
            <input ref={fileRef} type="file" accept=".xlsx,.xls" className="hidden"
              onChange={(e) => { const f = e.target.files?.[0]; if (f) void importExcel(f); e.target.value = ""; }} />
          </div>
        )}
      </Card>

      {showNewMember && (
        <NewMemberForm entityId={entityId} types={types} onDone={() => { setShowNewMember(false); refreshMembers(); }} />
      )}

      {selectedMemberId ? (
        <MemberDetail memberId={selectedMemberId} types={types} onBack={() => setSelectedMemberId(null)} onChanged={refreshMembers} />
      ) : (
        <Card className="overflow-hidden">
          {membersQuery.isLoading ? (
            <Skeleton />
          ) : members.length === 0 ? (
            <EmptyState
              title="Añade tu primer socio"
              message="Esta entidad aún no tiene socios. Crea el primero para empezar a gestionar cuotas."
              action={<Button onClick={() => setShowNewMember(true)}>Nuevo socio</Button>}
            />
          ) : (
            <div className="overflow-x-auto">
            <table className="w-full min-w-[640px] text-sm">
              <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
                <tr>
                  <th className="px-5 py-3 font-medium">Nº</th>
                  <th className="px-5 py-3 font-medium">Socio</th>
                  <th className="px-5 py-3 font-medium">Tipo</th>
                  <th className="px-5 py-3 font-medium">Contacto</th>
                  <th className="px-5 py-3 font-medium">Estado</th>
                  <th className="px-5 py-3"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {members.map((m) => (
                  <tr key={m.id} onClick={() => setSelectedMemberId(m.id)} className="cursor-pointer hover:bg-canvas">
                    <td className="px-5 py-3 text-ink-soft">{m.member_number ?? "—"}</td>
                    <td className="px-5 py-3">
                      <div className="flex items-center gap-3">
                        <Avatar name={m.full_name} />
                        <span className="font-medium text-ink">{m.full_name}</span>
                      </div>
                    </td>
                    <td className="px-5 py-3 text-ink-soft">{m.member_type?.name ?? "—"}</td>
                    <td className="px-5 py-3 text-ink-soft">{m.email ?? m.phone ?? "—"}</td>
                    <td className="px-5 py-3"><Badge tone={STATUS_TONES[m.status] ?? "neutral"}>{m.status.replace("_", " ")}</Badge></td>
                    <td className="px-5 py-3 text-right">
                      <button onClick={(e) => { e.stopPropagation(); void removeMember(m.id, m.full_name); }} className="text-xs text-red-600 hover:underline">Eliminar</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            </div>
          )}
        </Card>
      )}
      {!selectedMemberId && <Pagination meta={membersQuery.data?.meta} onPage={setPage} />}
    </div>
  );
}

function NewMemberForm({ entityId, types, onDone }: { entityId: string; types: MemberType[]; onDone: () => void }) {
  const [form, setForm] = useState({ first_name: "", last_name: "", email: "", phone: "", member_type_id: "" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const toast = useToast();
  const set = (k: string, v: string) => setForm((p) => ({ ...p, [k]: v }));
  const emailError = form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email) ? "Email no válido" : null;

  async function submit() {
    setError(null); setBusy(true);
    try {
      await api(`/entities/${entityId}/members`, { method: "POST", body: {
        first_name: form.first_name, last_name: form.last_name || null, email: form.email || null,
        phone: form.phone || null, member_type_id: form.member_type_id || null,
      } });
      toast.success("Socio creado.");
      onDone();
    } catch (err) { setError(err instanceof ApiError ? err.message : "No se pudo crear el socio"); } finally { setBusy(false); }
  }

  return (
    <Card className="p-5">
      <h2 className="mb-4 text-lg font-semibold text-primary">Nuevo socio</h2>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <TextField label="Nombre" value={form.first_name} onChange={(v) => set("first_name", v)} />
        <TextField label="Apellidos" value={form.last_name} onChange={(v) => set("last_name", v)} />
        <SelectField label="Tipo de socio" value={form.member_type_id} onChange={(v) => set("member_type_id", v)}
          options={[["", "Sin asignar"], ...types.map((t) => [t.id, `${t.name} (${t.fee_amount} €)`] as const)]} />
        <div>
          <TextField label="Email" value={form.email} onChange={(v) => set("email", v)} />
          {emailError && <p className="mt-1 text-xs text-red-600">{emailError}</p>}
        </div>
        <TextField label="Teléfono" value={form.phone} onChange={(v) => set("phone", v)} />
      </div>
      <div className="mt-4"><Button onClick={submit} disabled={busy || !form.first_name || !!emailError}>{busy ? "Creando…" : "Crear socio"}</Button></div>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
    </Card>
  );
}

type FullMember = {
  id: string; member_number: string | null; first_name: string; last_name: string | null;
  dni: string | null; birth_date: string | null; email: string | null; phone: string | null;
  address: string | null; city: string | null; postal_code: string | null; status: string; member_type_id: string | null;
};
type Payment = { id: string; year: number; amount: number; status: string; payment_date: string | null; payment_method: string | null };

function MemberDetail({ memberId, types, onBack, onChanged }: { memberId: string; types: MemberType[]; onBack: () => void; onChanged: () => void }) {
  const [member, setMember] = useState<FullMember | null>(null);
  const [payments, setPayments] = useState<Payment[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const toast = useToast();

  const load = useCallback(async () => {
    const [m, p] = await Promise.all([
      api<{ data: FullMember }>(`/members/${memberId}`),
      api<{ data: Payment[] }>(`/members/${memberId}/payments`),
    ]);
    setMember(m.data);
    setPayments(p.data);
  }, [memberId]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  function set<K extends keyof FullMember>(key: K, value: FullMember[K]) {
    setMember((prev) => (prev ? { ...prev, [key]: value } : prev));
  }

  async function save() {
    if (!member) return;
    setError(null); setSaving(true);
    try {
      await api(`/members/${memberId}`, { method: "PUT", body: {
        first_name: member.first_name, last_name: member.last_name || null, dni: member.dni || null,
        birth_date: member.birth_date || null, email: member.email || null, phone: member.phone || null,
        address: member.address || null, city: member.city || null, postal_code: member.postal_code || null,
        status: member.status, member_type_id: member.member_type_id || null,
      } });
      toast.success("Cambios guardados.");
      onChanged();
    } catch (err) { setError(err instanceof ApiError ? err.message : "No se pudo guardar"); } finally { setSaving(false); }
  }

  async function removePayment(id: string) { await api(`/member-payments/${id}`, { method: "DELETE" }); await load(); }

  if (!member) return <Spinner />;

  return (
    <div className="space-y-4">
      <button onClick={onBack} className="text-sm text-primary hover:underline">← Volver al listado</button>

      <Card className="p-5">
        <div className="mb-4 flex flex-wrap items-center gap-3">
          <Avatar name={`${member.first_name} ${member.last_name ?? ""}`} />
          <h2 className="text-lg font-semibold text-primary">{member.first_name} {member.last_name}</h2>
          {member.member_number && <Badge tone="neutral">Nº {member.member_number}</Badge>}
          <Badge tone={STATUS_TONES[member.status] ?? "neutral"}>{member.status.replace("_", " ")}</Badge>
          <span className="ml-auto flex gap-2">
            <Button variant="ghost" onClick={() => void downloadFile(`/members/${memberId}/sheet`, { method: "GET", fallbackName: "ficha.pdf" })}>Ficha PDF</Button>
            <Button variant="ghost" onClick={() => void downloadFile(`/members/${memberId}/card`, { method: "GET", fallbackName: "carnet.pdf" })}>Carnet PDF</Button>
          </span>
        </div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <TextField label="Nombre" value={member.first_name} onChange={(v) => set("first_name", v)} />
          <TextField label="Apellidos" value={member.last_name ?? ""} onChange={(v) => set("last_name", v)} />
          <TextField label="DNI" value={member.dni ?? ""} onChange={(v) => set("dni", v)} />
          <DateInput label="Fecha de nacimiento" value={member.birth_date ?? ""} onChange={(v) => set("birth_date", v)} />
          <TextField label="Email" value={member.email ?? ""} onChange={(v) => set("email", v)} />
          <TextField label="Teléfono" value={member.phone ?? ""} onChange={(v) => set("phone", v)} />
          <TextField label="Dirección" value={member.address ?? ""} onChange={(v) => set("address", v)} />
          <TextField label="Ciudad" value={member.city ?? ""} onChange={(v) => set("city", v)} />
          <TextField label="Código postal" value={member.postal_code ?? ""} onChange={(v) => set("postal_code", v)} />
          <SelectField label="Tipo de socio" value={member.member_type_id ?? ""} onChange={(v) => set("member_type_id", v || null)}
            options={[["", "Sin asignar"], ...types.map((t) => [t.id, t.name] as const)]} />
          <SelectField label="Estado" value={member.status} onChange={(v) => set("status", v)} options={STATUSES} />
        </div>
        {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
        <div className="mt-4"><Button onClick={save} disabled={saving}>{saving ? "Guardando…" : "Guardar cambios"}</Button></div>
      </Card>

      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Historial de pagos</h3>
        {payments.length === 0 ? <p className="mb-4 text-sm text-ink-soft">Sin pagos registrados.</p> : (
          <table className="mb-4 w-full text-sm">
            <thead className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr><th className="py-2 font-medium">Ejercicio</th><th className="py-2 font-medium">Importe</th><th className="py-2 font-medium">Estado</th><th className="py-2 font-medium">Método</th><th className="py-2 font-medium">Fecha</th><th className="py-2"></th></tr>
            </thead>
            <tbody className="divide-y divide-line">
              {payments.map((p) => (
                <tr key={p.id}>
                  <td className="py-2">{p.year}</td>
                  <td className="py-2 font-medium text-ink">{eur(p.amount)}</td>
                  <td className="py-2"><Badge tone={p.status === "pagado" ? "ok" : p.status === "parcial" ? "info" : "warn"}>{p.status}</Badge></td>
                  <td className="py-2 text-ink-soft">{p.payment_method ?? "—"}</td>
                  <td className="py-2 text-ink-soft">{p.payment_date ?? "—"}</td>
                  <td className="py-2 text-right">
                    <button onClick={() => void downloadFile(`/member-payments/${p.id}/receipt`, { method: "GET", fallbackName: "recibo.pdf" })} className="mr-3 text-xs text-primary hover:underline">Recibo</button>
                    <button onClick={() => removePayment(p.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
        <NewPaymentForm memberId={memberId} onDone={load} />
      </Card>
    </div>
  );
}

function NewPaymentForm({ memberId, onDone }: { memberId: string; onDone: () => Promise<void> }) {
  const [form, setForm] = useState({
    year: String(new Date().getFullYear()), amount: "", status: "pagado",
    payment_method: "transferencia", payment_date: new Date().toISOString().slice(0, 10),
  });
  const [busy, setBusy] = useState(false);

  async function submit() {
    setBusy(true);
    try {
      await api(`/members/${memberId}/payments`, { method: "POST", body: {
        year: Number(form.year), amount: form.amount ? Number(form.amount) : undefined, status: form.status,
        payment_method: form.payment_method, payment_date: form.status === "pendiente" ? null : form.payment_date,
      } });
      setForm((p) => ({ ...p, amount: "" }));
      await onDone();
    } finally { setBusy(false); }
  }

  return (
    <div className="flex flex-wrap items-end gap-2 border-t border-line pt-4">
      <TextField label="Ejercicio" type="number" value={form.year} onChange={(v) => setForm((p) => ({ ...p, year: v }))} className="w-24" />
      <TextField label="Importe (€)" type="number" value={form.amount} onChange={(v) => setForm((p) => ({ ...p, amount: v }))} placeholder="Cuota del tipo" className="w-32" />
      <SelectField label="Estado" value={form.status} onChange={(v) => setForm((p) => ({ ...p, status: v }))} options={PAY_STATUSES} />
      <SelectField label="Método" value={form.payment_method} onChange={(v) => setForm((p) => ({ ...p, payment_method: v }))} options={PAY_METHODS} />
      <DateInput label="Fecha" value={form.payment_date} onChange={(v) => setForm((p) => ({ ...p, payment_date: v }))} />
      <Button variant="secondary" onClick={submit} disabled={busy}>{busy ? "Registrando…" : "Registrar pago"}</Button>
    </div>
  );
}
