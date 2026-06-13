"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError, uploadFile } from "@/lib/api";
import { useActiveCompany } from "@/lib/company";
import { useToast } from "@/lib/toast";
import { formatDate } from "@/lib/utils";
import { Badge, Button, Card, EmptyState, Field, Modal, SelectField, Spinner, TextField, Toggle } from "@/components/ui";
import { DateInput } from "@/components/DateInput";
import { CalendarDetail } from "./calendar-detail";

type Company = { id: string; name: string; cif: string; email: string | null; phone: string | null; company_group_id: string | null; group?: { id: string; name: string } | null };
type Group = { id: string; name: string; companies_count?: number };

/** Empresa activa del header. Devuelve "" si no hay ninguna seleccionada. */
function useCompanyId(): string {
  return useActiveCompany()?.activeId ?? "";
}

const NO_COMPANY = (
  <EmptyState title="Selecciona una empresa" message="Selecciona una empresa en el menú superior para ver esta sección." />
);

// ─────────────────────────────────────────── Módulos ───────────────────────────────────────────

type Module = { key: string; label: string; description: string; enabled: boolean };

export function ModulosSection() {
  const [modules, setModules] = useState<Module[] | null>(null);

  const load = useCallback(async () => {
    const res = await api<{ data: Module[] }>("/tenant-modules");
    setModules(res.data);
  }, []);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function toggle(m: Module) {
    setModules((prev) => prev?.map((x) => (x.key === m.key ? { ...x, enabled: !x.enabled } : x)) ?? null);
    try {
      await api(`/tenant-modules/${m.key}`, { method: "PATCH", body: { enabled: !m.enabled } });
    } catch {
      void load();
    }
  }

  if (!modules) return <Spinner />;

  return (
    <div>
      <p className="mb-4 text-sm text-ink-soft">Activa los módulos que usa este cliente. El menú lateral se actualiza al recargar la página.</p>
      <div className="grid gap-3 sm:grid-cols-2">
        {modules.map((m) => (
          <Card key={m.key} className="flex items-start justify-between gap-4 p-4">
            <div>
              <p className="font-medium text-ink">{m.label}</p>
              <p className="mt-1 text-sm text-ink-soft">{m.description}</p>
            </div>
            <Toggle on={m.enabled} onClick={() => toggle(m)} />
          </Card>
        ))}
      </div>
    </div>
  );
}

// ─────────────────────────────────────── Empresas y grupos ───────────────────────────────────────

export function EmpresasSection() {
  const [companies, setCompanies] = useState<Company[]>([]);
  const [groups, setGroups] = useState<Group[]>([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState<Company | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [groupName, setGroupName] = useState("");

  const load = useCallback(async () => {
    const [c, g] = await Promise.all([
      api<{ data: Company[] }>("/companies"),
      api<{ data: Group[] }>("/company-groups"),
    ]);
    setCompanies(c.data);
    setGroups(g.data);
    setLoading(false);
  }, []);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function createGroup() {
    if (!groupName.trim()) return;
    await api("/company-groups", { method: "POST", body: { name: groupName } });
    setGroupName("");
    await load();
  }
  async function deleteGroup(id: string) { await api(`/company-groups/${id}`, { method: "DELETE" }); await load(); }
  async function deleteCompany(id: string) { try { await api(`/companies/${id}`, { method: "DELETE" }); await load(); } catch (e) { alert(e instanceof ApiError ? e.message : "Error"); } }

  if (loading) return <Spinner />;

  return (
    <div className="space-y-6">
      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Grupos de empresas</h3>
        <div className="mb-4 flex flex-wrap gap-2">
          {groups.length === 0 && <span className="text-sm text-ink-soft">Sin grupos.</span>}
          {groups.map((g) => (
            <span key={g.id} className="inline-flex items-center gap-2 rounded-full bg-secondary/15 px-3 py-1 text-xs text-primary">
              {g.name}{typeof g.companies_count === "number" && <span className="text-ink-soft">({g.companies_count})</span>}
              <button onClick={() => deleteGroup(g.id)} className="text-ink-soft hover:text-red-600">×</button>
            </span>
          ))}
        </div>
        <div className="flex items-end gap-2">
          <TextField label="Nuevo grupo" value={groupName} onChange={setGroupName} placeholder="Grupo Datarecover" className="w-72" />
          <Button variant="secondary" onClick={createGroup} disabled={!groupName.trim()}>Añadir</Button>
        </div>
      </Card>

      <Card className="overflow-hidden">
        <div className="flex items-center justify-between border-b border-line px-5 py-3">
          <h3 className="text-sm font-semibold text-primary">Empresas</h3>
          <Button onClick={() => { setEditing(null); setShowForm(true); }}>Nueva empresa</Button>
        </div>
        {companies.length === 0 ? (
          <p className="p-6 text-sm text-ink-soft">Sin empresas.</p>
        ) : (
          <table className="w-full text-sm">
            <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr><th className="px-5 py-3 font-medium">Empresa</th><th className="px-5 py-3 font-medium">Grupo</th><th className="px-5 py-3 font-medium">Contacto</th><th className="px-5 py-3"></th></tr>
            </thead>
            <tbody className="divide-y divide-line">
              {companies.map((c) => (
                <tr key={c.id}>
                  <td className="px-5 py-3"><p className="font-medium text-ink">{c.name}</p><p className="text-xs text-ink-soft">{c.cif}</p></td>
                  <td className="px-5 py-3">{c.group ? <Badge tone="info">{c.group.name}</Badge> : <span className="text-xs text-ink-soft">—</span>}</td>
                  <td className="px-5 py-3 text-ink-soft">{c.email ?? "—"}</td>
                  <td className="px-5 py-3 text-right">
                    <button onClick={() => { setEditing(c); setShowForm(true); }} className="mr-3 text-xs font-medium text-primary hover:underline">Editar</button>
                    <button onClick={() => deleteCompany(c.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </Card>

      {showForm && (
        <CompanyForm company={editing} groups={groups} onClose={() => setShowForm(false)} onSaved={() => { setShowForm(false); void load(); }} />
      )}
    </div>
  );
}

function CompanyForm({ company, groups, onClose, onSaved }: { company: Company | null; groups: Group[]; onClose: () => void; onSaved: () => void }) {
  const [form, setForm] = useState({
    name: company?.name ?? "", cif: company?.cif ?? "", email: company?.email ?? "", phone: company?.phone ?? "",
    company_group_id: company?.company_group_id ?? "",
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  async function submit() {
    setError(null); setBusy(true);
    try {
      const body = { name: form.name, cif: form.cif, email: form.email || null, phone: form.phone || null, company_group_id: form.company_group_id || null };
      await api(company ? `/companies/${company.id}` : "/companies", { method: company ? "PUT" : "POST", body });
      onSaved();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo guardar"); } finally { setBusy(false); }
  }

  return (
    <Modal title={company ? "Editar empresa" : "Nueva empresa"} onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-2">
        <TextField label="Nombre" value={form.name} onChange={(v) => set("name", v)} />
        <TextField label="CIF" value={form.cif} onChange={(v) => set("cif", v)} />
        <TextField label="Email" value={form.email} onChange={(v) => set("email", v)} />
        <TextField label="Teléfono" value={form.phone} onChange={(v) => set("phone", v)} />
        <SelectField label="Grupo" value={form.company_group_id} onChange={(v) => set("company_group_id", v)}
          options={[["", "Sin grupo"], ...groups.map((g) => [g.id, g.name] as const)]} />
      </div>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.name || !form.cif}>{busy ? "Guardando…" : "Guardar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}

// ─────────────────────────────────────── Centros de trabajo ───────────────────────────────────────

type WorkCenter = {
  id: string;
  name: string;
  address: string | null;
  timezone: string | null;
  location_required: boolean;
  agreement_ids?: string[];
};
type AgreementOption = { id: string; name: string };

export function CentrosSection() {
  const companyId = useCompanyId();
  const [centers, setCenters] = useState<WorkCenter[]>([]);
  const [agreements, setAgreements] = useState<AgreementOption[]>([]);
  const [editing, setEditing] = useState<WorkCenter | null>(null);
  const [showForm, setShowForm] = useState(false);
  const toast = useToast();

  const load = useCallback(async () => {
    if (!companyId) return;
    const [c, a] = await Promise.all([
      api<{ data: WorkCenter[] }>(`/companies/${companyId}/work-centers`),
      api<{ data: AgreementOption[] }>(`/agreements?company_id=${companyId}`),
    ]);
    setCenters(c.data);
    setAgreements(a.data);
  }, [companyId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function remove(id: string) {
    try { await api(`/work-centers/${id}`, { method: "DELETE" }); await load(); }
    catch (e) { toast.error(e instanceof ApiError ? e.message : "Error"); }
  }

  if (!companyId) return NO_COMPANY;

  return (
    <div className="space-y-4">
      <Card className="overflow-hidden">
        <div className="flex items-center justify-between border-b border-line px-5 py-3">
          <h3 className="text-sm font-semibold text-primary">Centros de trabajo</h3>
          <Button onClick={() => { setEditing(null); setShowForm(true); }}>Nuevo centro</Button>
        </div>
        {centers.length === 0 ? <p className="p-6 text-sm text-ink-soft">Sin centros.</p> : (
          <ul className="divide-y divide-line">
            {centers.map((c) => (
              <li key={c.id} className="flex items-center justify-between px-5 py-3 text-sm">
                <span className="flex flex-wrap items-center gap-2">
                  <span className="font-medium text-ink">{c.name}</span>
                  {c.address && <span className="text-ink-soft">· {c.address}</span>}
                  {c.location_required && <Badge tone="info">Geolocalización</Badge>}
                  {(c.agreement_ids?.length ?? 0) > 0 && <Badge tone="neutral">{c.agreement_ids!.length} convenio(s)</Badge>}
                </span>
                <span className="flex gap-3">
                  <button onClick={() => { setEditing(c); setShowForm(true); }} className="text-xs font-medium text-primary hover:underline">Editar</button>
                  <button onClick={() => remove(c.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                </span>
              </li>
            ))}
          </ul>
        )}
      </Card>

      {showForm && (
        <WorkCenterForm
          companyId={companyId}
          center={editing}
          agreements={agreements}
          onClose={() => setShowForm(false)}
          onSaved={() => { setShowForm(false); void load(); }}
        />
      )}
    </div>
  );
}

function WorkCenterForm({ companyId, center, agreements, onClose, onSaved }: {
  companyId: string;
  center: WorkCenter | null;
  agreements: AgreementOption[];
  onClose: () => void;
  onSaved: () => void;
}) {
  const [form, setForm] = useState({
    name: center?.name ?? "",
    address: center?.address ?? "",
    timezone: center?.timezone ?? "Europe/Madrid",
    location_required: center?.location_required ?? false,
  });
  const [agreementIds, setAgreementIds] = useState<string[]>(center?.agreement_ids ?? []);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function toggleAgreement(id: string) {
    setAgreementIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  }

  async function submit() {
    setError(null); setBusy(true);
    try {
      const body = {
        name: form.name,
        address: form.address || null,
        timezone: form.timezone || null,
        location_required: form.location_required,
        agreement_ids: agreementIds,
      };
      await api(center ? `/work-centers/${center.id}` : `/companies/${companyId}/work-centers`,
        { method: center ? "PUT" : "POST", body });
      onSaved();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo guardar"); } finally { setBusy(false); }
  }

  return (
    <Modal title={center ? "Editar centro" : "Nuevo centro de trabajo"} onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-3">
        <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} />
        <TextField label="Dirección" value={form.address} onChange={(v) => setForm((p) => ({ ...p, address: v }))} />
        <TextField label="Zona horaria" value={form.timezone} onChange={(v) => setForm((p) => ({ ...p, timezone: v }))} />
      </div>
      <div className="mt-3">
        <Toggle
          on={form.location_required}
          onClick={() => setForm((p) => ({ ...p, location_required: !p.location_required }))}
          label="Exigir geolocalización al fichar presencialmente"
        />
      </div>
      <Field label="Convenios aplicables" className="mt-3">
        {agreements.length === 0 ? (
          <p className="text-sm text-ink-soft">No hay convenios para esta empresa.</p>
        ) : (
          <div className="flex flex-col gap-1.5 rounded-[var(--radius-fluent)] border border-line bg-canvas/60 p-3">
            {agreements.map((a) => (
              <label key={a.id} className="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" checked={agreementIds.includes(a.id)} onChange={() => toggleAgreement(a.id)} className="accent-secondary" />
                {a.name}
              </label>
            ))}
          </div>
        )}
      </Field>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.name}>{busy ? "Guardando…" : "Guardar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}

// ─────────────────────────────────────────── Convenios ───────────────────────────────────────────

type Agreement = { id: string; name: string; annual_hours: number; vacation_days: number; vacation_type: string };
type LeaveType = { id: string; name: string; type: string; count_in: string };

export function ConveniosSection() {
  const companyId = useCompanyId();
  const [agreements, setAgreements] = useState<Agreement[]>([]);
  const [form, setForm] = useState({ name: "", annual_hours: "1780", vacation_days: "22", vacation_type: "laborables" });
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!companyId) return;
    const res = await api<{ data: Agreement[] }>(`/agreements?company_id=${companyId}`);
    setAgreements(res.data);
  }, [companyId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function create() {
    setError(null);
    try {
      await api("/agreements", { method: "POST", body: {
        company_id: companyId, name: form.name, annual_hours: Number(form.annual_hours),
        vacation_days: Number(form.vacation_days), vacation_type: form.vacation_type,
      } });
      setForm({ name: "", annual_hours: "1780", vacation_days: "22", vacation_type: "laborables" });
      await load();
    } catch (e) { setError(e instanceof ApiError ? e.message : "Error"); }
  }
  async function remove(id: string) { try { await api(`/agreements/${id}`, { method: "DELETE" }); await load(); } catch (e) { alert(e instanceof ApiError ? e.message : "Error"); } }

  if (!companyId) return NO_COMPANY;

  return (
    <div className="space-y-4">
      {agreements.map((a) => <AgreementCard key={a.id} agreement={a} onRemove={() => remove(a.id)} />)}
      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Nuevo convenio</h3>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} />
          <TextField label="Horas anuales" type="number" value={form.annual_hours} onChange={(v) => setForm((p) => ({ ...p, annual_hours: v }))} />
          <TextField label="Días vacaciones" type="number" value={form.vacation_days} onChange={(v) => setForm((p) => ({ ...p, vacation_days: v }))} />
          <SelectField label="Tipo vacaciones" value={form.vacation_type} onChange={(v) => setForm((p) => ({ ...p, vacation_type: v }))}
            options={[["laborables", "Laborables"], ["naturales", "Naturales"]]} />
        </div>
        {error && <p className="mt-2 text-sm text-red-700">{error}</p>}
        <div className="mt-3"><Button variant="secondary" onClick={create} disabled={!form.name}>Crear convenio</Button></div>
      </Card>
    </div>
  );
}

function AgreementCard({ agreement, onRemove }: { agreement: Agreement; onRemove: () => void }) {
  const [types, setTypes] = useState<LeaveType[]>([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ name: "", type: "ausencia", count_in: "dias" });

  const load = useCallback(async () => {
    const res = await api<{ data: LeaveType[] }>(`/agreements/${agreement.id}/leave-types`);
    setTypes(res.data);
  }, [agreement.id]);
  useEffect(() => { if (open) void (async () => { await load(); })(); }, [open, load]);

  async function addType() {
    await api(`/agreements/${agreement.id}/leave-types`, { method: "POST", body: form });
    setForm({ name: "", type: "ausencia", count_in: "dias" });
    await load();
  }
  async function removeType(id: string) { await api(`/leave-types/${id}`, { method: "DELETE" }); await load(); }

  return (
    <Card className="p-5">
      <div className="flex items-center justify-between">
        <div>
          <p className="font-medium text-ink">{agreement.name}</p>
          <p className="text-sm text-ink-soft">{agreement.annual_hours} h/año · {agreement.vacation_days} días ({agreement.vacation_type})</p>
        </div>
        <div className="flex gap-3">
          <button onClick={() => setOpen((v) => !v)} className="text-xs font-medium text-primary hover:underline">Tipos de ausencia</button>
          <button onClick={onRemove} className="text-xs text-red-600 hover:underline">Eliminar</button>
        </div>
      </div>
      {open && (
        <div className="mt-4 rounded-[var(--radius-fluent)] border border-line bg-canvas/60 p-4">
          {types.length === 0 ? <p className="mb-3 text-sm text-ink-soft">Sin tipos.</p> : (
            <ul className="mb-3 space-y-1 text-sm">
              {types.map((t) => (
                <li key={t.id} className="flex items-center justify-between">
                  <span className="text-ink">{t.name} <span className="text-ink-soft">({t.type}, en {t.count_in})</span></span>
                  <button onClick={() => removeType(t.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
                </li>
              ))}
            </ul>
          )}
          <div className="flex flex-wrap items-end gap-2">
            <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} className="w-40" />
            <SelectField label="Tipo" value={form.type} onChange={(v) => setForm((p) => ({ ...p, type: v }))} options={[["ausencia", "Ausencia"], ["presencia", "Presencia"]]} />
            <SelectField label="Cuenta en" value={form.count_in} onChange={(v) => setForm((p) => ({ ...p, count_in: v }))} options={[["dias", "Días"], ["horas", "Horas"]]} />
            <Button variant="secondary" onClick={addType} disabled={!form.name}>Añadir tipo</Button>
          </div>
        </div>
      )}
    </Card>
  );
}

// ─────────────────────────────────────── Hitos de fichaje ───────────────────────────────────────

type Milestone = { id: string; name: string; type: string; color: string; company_id: string };

export function HitosSection() {
  const companyId = useCompanyId();
  const [milestones, setMilestones] = useState<Milestone[]>([]);
  const [form, setForm] = useState({ name: "", type: "entrada", color: "#5eb8d0" });

  const load = useCallback(async () => {
    if (!companyId) return;
    const res = await api<{ data: Milestone[] }>("/milestones");
    setMilestones(res.data.filter((m) => m.company_id === companyId));
  }, [companyId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function create() {
    await api("/milestones", { method: "POST", body: { company_id: companyId, name: form.name, type: form.type, color: form.color } });
    setForm({ name: "", type: "entrada", color: "#5eb8d0" });
    await load();
  }
  async function remove(id: string) { await api(`/milestones/${id}`, { method: "DELETE" }); await load(); }

  if (!companyId) return NO_COMPANY;

  return (
    <div className="space-y-4">
      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Hitos de fichaje</h3>
        {milestones.length === 0 ? <p className="mb-4 text-sm text-ink-soft">Sin hitos.</p> : (
          <ul className="mb-4 divide-y divide-line">
            {milestones.map((m) => (
              <li key={m.id} className="flex items-center justify-between py-2 text-sm">
                <span className="flex items-center gap-2">
                  <span className="inline-block h-3 w-3 rounded-full" style={{ background: m.color }} />
                  <span className="font-medium text-ink">{m.name}</span>
                  <Badge tone={m.type === "entrada" ? "ok" : "info"}>{m.type}</Badge>
                </span>
                <button onClick={() => remove(m.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
              </li>
            ))}
          </ul>
        )}
        <div className="flex flex-wrap items-end gap-3">
          <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} className="w-40" />
          <SelectField label="Tipo" value={form.type} onChange={(v) => setForm((p) => ({ ...p, type: v }))} options={[["entrada", "Entrada"], ["salida", "Salida"]]} />
          <Field label="Color"><input type="color" value={form.color} onChange={(e) => setForm((p) => ({ ...p, color: e.target.value }))} className="h-9 w-16 rounded border border-line bg-canvas" /></Field>
          <Button variant="secondary" onClick={create} disabled={!form.name}>Añadir hito</Button>
        </div>
      </Card>
    </div>
  );
}

// ─────────────────────────────────────────── Festivos ───────────────────────────────────────────

type Holiday = { id: string; name: string; type: string; repeatable: boolean; date: string | null; day_of_year: number | null; province: string | null; work_center_ids?: string[] };
type CenterOption = { id: string; name: string };

export function FestivosSection() {
  const companyId = useCompanyId();
  const currentYear = new Date().getFullYear();
  const [year, setYear] = useState(String(currentYear));
  const [holidays, setHolidays] = useState<Holiday[]>([]);
  const [centers, setCenters] = useState<CenterOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ name: "", type: "local", date: "" });
  const [workCenterIds, setWorkCenterIds] = useState<string[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!companyId) { setLoading(false); return; }
    const [h, c] = await Promise.all([
      api<{ data: Holiday[] }>(`/holidays?company_id=${companyId}&year=${year}`),
      api<{ data: CenterOption[] }>(`/companies/${companyId}/work-centers`),
    ]);
    setHolidays(h.data);
    setCenters(c.data);
    setLoading(false);
  }, [companyId, year]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  function toggleCenter(id: string) {
    setWorkCenterIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
  }

  async function create() {
    setError(null);
    try {
      await api("/holidays", { method: "POST", body: { name: form.name, type: form.type, repeatable: false, date: form.date, work_center_ids: workCenterIds } });
      setForm({ name: "", type: "local", date: "" });
      setWorkCenterIds([]);
      await load();
    } catch (e) { setError(e instanceof ApiError ? e.message : "Error"); }
  }
  async function remove(id: string) { await api(`/holidays/${id}`, { method: "DELETE" }); await load(); }

  if (!companyId) return NO_COMPANY;
  if (loading) return <Spinner />;

  const years = Array.from({ length: 5 }, (_, i) => String(currentYear - 2 + i));

  return (
    <Card className="p-5">
      <div className="mb-3 flex items-center justify-between gap-3">
        <h3 className="text-sm font-semibold text-primary">Festivos</h3>
        <SelectField label="Año" value={year} onChange={setYear} className="w-32" options={years.map((y) => [y, y] as const)} />
      </div>
      {holidays.length === 0 ? <p className="mb-4 text-sm text-ink-soft">Sin festivos.</p> : (
        <ul className="mb-4 max-h-80 divide-y divide-line overflow-y-auto">
          {holidays.map((h) => (
            <li key={h.id} className="flex items-center justify-between py-2 text-sm">
              <span>
                <span className="font-medium text-ink">{h.name}</span>{" "}
                <span className="text-ink-soft">· {h.repeatable ? `día ${h.day_of_year}` : formatDate(h.date)} · {h.type}{(h.work_center_ids?.length ?? 0) === 0 ? " · nacional" : ""}</span>
              </span>
              <button onClick={() => remove(h.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
            </li>
          ))}
        </ul>
      )}
      <div className="flex flex-wrap items-end gap-3">
        <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} className="w-48" />
        <SelectField label="Tipo" value={form.type} onChange={(v) => setForm((p) => ({ ...p, type: v }))} options={[["nacional", "Nacional"], ["autonomico", "Autonómico"], ["local", "Local"]]} />
        <DateInput label="Fecha" value={form.date} onChange={(v) => setForm((p) => ({ ...p, date: v }))} />
        <Button variant="secondary" onClick={create} disabled={!form.name || !form.date}>Añadir festivo</Button>
      </div>
      <Field label="Centros de trabajo (vacío = nacional, aplica a todos)" className="mt-3">
        {centers.length === 0 ? (
          <p className="text-sm text-ink-soft">No hay centros para esta empresa.</p>
        ) : (
          <div className="flex flex-wrap gap-x-4 gap-y-1.5 rounded-[var(--radius-fluent)] border border-line bg-canvas/60 p-3">
            {centers.map((c) => (
              <label key={c.id} className="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" checked={workCenterIds.includes(c.id)} onChange={() => toggleCenter(c.id)} className="accent-secondary" />
                {c.name}
              </label>
            ))}
          </div>
        )}
      </Field>
      {error && <p className="mt-2 text-sm text-red-700">{error}</p>}
    </Card>
  );
}

// ─────────────────────────────────────────── Marca blanca ───────────────────────────────────────────

type Branding = { app_name: string | null; primary_color: string | null; font: string | null; logo_path: string | null; custom_domain: string | null };

const FONTS = [
  ["IBM Plex Sans", "IBM Plex Sans"],
  ["Inter", "Inter"],
  ["Roboto", "Roboto"],
  ["Open Sans", "Open Sans"],
] as const;

export function MarcaBlancaSection() {
  const [form, setForm] = useState<Branding | null>(null);
  const [busy, setBusy] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [dragOver, setDragOver] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const toast = useToast();

  const load = useCallback(async () => {
    const res = await api<{ data: Branding }>("/branding");
    setForm(res.data);
  }, []);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  function set(k: keyof Branding, v: string) { setForm((p) => (p ? { ...p, [k]: v } : p)); }

  async function save() {
    if (!form) return;
    setError(null); setBusy(true);
    try {
      await api("/branding", { method: "PUT", body: {
        app_name: form.app_name || null,
        primary_color: form.primary_color || null,
        font: form.font || null,
        logo_path: form.logo_path || null,
        custom_domain: form.custom_domain || null,
      } });
      toast.success("Branding guardado. Recarga para aplicar los cambios globalmente.");
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo guardar"); } finally { setBusy(false); }
  }

  async function upload(file: File) {
    setUploading(true); setError(null);
    try {
      const res = await uploadFile<{ data: Branding }>("/branding/logo", file);
      setForm(res.data);
      toast.success("Logo subido correctamente.");
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo subir el logo"); } finally { setUploading(false); }
  }

  if (!form) return <Spinner />;

  const color = form.primary_color || "#0F2756";
  const font = form.font || "IBM Plex Sans";

  return (
    <div className="space-y-4">
      <Card className="p-5">
        <h3 className="mb-1 text-sm font-semibold text-primary">Dominio propio</h3>
        <p className="mb-4 text-sm text-ink-soft">Configura tu dominio (CNAME → tu tenant en gestioname.app). Ej.: <code>app.tuempresa.com</code>.</p>
        <TextField label="Dominio personalizado" value={form.custom_domain ?? ""} onChange={(v) => set("custom_domain", v)} placeholder="app.tuempresa.com" className="max-w-md" />
      </Card>

      <Card className="p-5">
        <h3 className="mb-4 text-sm font-semibold text-primary">Personalización visual</h3>
        <div className="grid gap-4 lg:grid-cols-2">
          <div className="space-y-3">
            <TextField label="Nombre de la app" value={form.app_name ?? ""} onChange={(v) => set("app_name", v)} />
            <Field label="Color principal">
              <div className="flex items-center gap-2">
                <input type="color" value={color} onChange={(e) => set("primary_color", e.target.value)} className="h-9 w-16 rounded border border-line bg-canvas" />
                <input
                  type="text"
                  value={form.primary_color ?? ""}
                  onChange={(e) => set("primary_color", e.target.value)}
                  placeholder="#0F2756"
                  className="w-32 rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                />
              </div>
            </Field>
            <SelectField label="Tipografía" value={font} onChange={(v) => set("font", v)} options={FONTS} />

            <Field label="Logo (subir fichero)">
              <div
                onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                onDragLeave={() => setDragOver(false)}
                onDrop={(e) => {
                  e.preventDefault();
                  setDragOver(false);
                  const file = e.dataTransfer.files?.[0];
                  if (file) void upload(file);
                }}
                className={`flex flex-col items-center justify-center gap-2 rounded-[var(--radius-fluent)] border-2 border-dashed p-5 text-center text-sm transition-colors ${dragOver ? "border-secondary bg-secondary/10" : "border-line bg-canvas/60"}`}
              >
                <p className="text-ink-soft">{uploading ? "Subiendo…" : "Arrastra una imagen aquí o"}</p>
                <label className="cursor-pointer text-primary hover:underline">
                  selecciona un archivo
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                    className="hidden"
                    onChange={(e) => { const f = e.target.files?.[0]; if (f) void upload(f); e.target.value = ""; }}
                  />
                </label>
              </div>
            </Field>
            <TextField label="URL del logo (alternativa externa)" value={form.logo_path ?? ""} onChange={(v) => set("logo_path", v)} placeholder="https://…/logo.png" />
          </div>

          <div>
            <span className="mb-1.5 block text-sm font-medium text-ink">Vista previa</span>
            <div className="rounded-[var(--radius-fluent)] border border-line bg-surface p-5" style={{ fontFamily: font }}>
              <div className="flex items-center gap-3">
                {form.logo_path ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={form.logo_path} alt="Logo" className="h-10 w-auto max-w-[160px] object-contain" />
                ) : (
                  <span className="flex h-10 w-10 items-center justify-center rounded-[var(--radius-fluent)] text-sm font-bold text-white" style={{ background: color }}>
                    {(form.app_name || "G").charAt(0).toUpperCase()}
                  </span>
                )}
                <span className="text-xl font-semibold" style={{ color }}>{form.app_name || "Gestioname"}</span>
              </div>
              <div className="mt-4 flex flex-wrap items-center gap-2">
                <span className="inline-flex rounded-full px-3 py-1 text-xs font-medium text-white" style={{ background: color }}>Color principal</span>
                <span className="inline-block h-6 w-6 rounded-full border border-line" style={{ background: color }} />
                <span className="text-sm text-ink-soft">{color}</span>
              </div>
              <p className="mt-4 text-sm text-ink-soft">Texto de ejemplo en la tipografía <strong>{font}</strong> seleccionada.</p>
            </div>
          </div>
        </div>
        {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
        <div className="mt-4"><Button onClick={save} disabled={busy}>{busy ? "Guardando…" : "Guardar branding"}</Button></div>
      </Card>
    </div>
  );
}

// ─────────────────────────────────────────── Calendarios ───────────────────────────────────────────

export type Template = { id: string; name: string; type: string; year: number; color: string; daily_hours: number | null };
type Calendar = { id: string; name: string; year: number; days_count?: number; company_id: string; color: string };

export function CalendariosSection() {
  const companyId = useCompanyId();
  const [templates, setTemplates] = useState<Template[]>([]);
  const [calendars, setCalendars] = useState<Calendar[]>([]);
  const [selected, setSelected] = useState<Calendar | null>(null);
  const year = new Date().getFullYear();
  const [tplForm, setTplForm] = useState({ name: "", type: "fijo", color: "#0f2756" });
  const [calForm, setCalForm] = useState({ name: "", color: "#5eb8d0" });

  const load = useCallback(async () => {
    if (!companyId) return;
    const [t, c] = await Promise.all([
      api<{ data: Template[] }>(`/schedule-templates?company_id=${companyId}`),
      api<{ data: Calendar[] }>(`/calendars?company_id=${companyId}`),
    ]);
    setTemplates(t.data);
    setCalendars(c.data.filter((x) => x.company_id === companyId));
  }, [companyId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function createTemplate() {
    await api("/schedule-templates", { method: "POST", body: { company_id: companyId, name: tplForm.name, type: tplForm.type, color: tplForm.color, year } });
    setTplForm({ name: "", type: "fijo", color: "#0f2756" });
    await load();
  }
  async function createCalendar() {
    await api("/calendars", { method: "POST", body: { company_id: companyId, name: calForm.name, color: calForm.color, year } });
    setCalForm({ name: "", color: "#5eb8d0" });
    await load();
  }

  if (!companyId) return NO_COMPANY;

  return (
    <div className="space-y-4">
      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Plantillas de horario ({year})</h3>
        {templates.length === 0 ? <p className="mb-4 text-sm text-ink-soft">Sin plantillas.</p> : (
          <ul className="mb-4 divide-y divide-line">
            {templates.map((t) => (
              <li key={t.id} className="flex items-center gap-2 py-2 text-sm">
                <span className="inline-block h-3 w-3 rounded-full" style={{ background: t.color }} />
                <span className="font-medium text-ink">{t.name}</span>
                <Badge tone="neutral">{t.type}</Badge>
                {t.daily_hours != null && <span className="text-ink-soft">{t.daily_hours} h/día</span>}
              </li>
            ))}
          </ul>
        )}
        <div className="flex flex-wrap items-end gap-3">
          <TextField label="Nombre" value={tplForm.name} onChange={(v) => setTplForm((p) => ({ ...p, name: v }))} className="w-44" />
          <SelectField label="Tipo" value={tplForm.type} onChange={(v) => setTplForm((p) => ({ ...p, type: v }))} options={[["fijo", "Fijo"], ["flexible", "Flexible"], ["libre", "Libre"]]} />
          <Field label="Color"><input type="color" value={tplForm.color} onChange={(e) => setTplForm((p) => ({ ...p, color: e.target.value }))} className="h-9 w-16 rounded border border-line bg-canvas" /></Field>
          <Button variant="secondary" onClick={createTemplate} disabled={!tplForm.name}>Añadir plantilla</Button>
        </div>
      </Card>

      <Card className="p-5">
        <h3 className="mb-3 text-sm font-semibold text-primary">Calendarios anuales ({year})</h3>
        {calendars.length === 0 ? <p className="mb-4 text-sm text-ink-soft">Sin calendarios.</p> : (
          <ul className="mb-4 divide-y divide-line">
            {calendars.map((c) => (
              <li key={c.id} className="flex items-center justify-between py-2 text-sm">
                <span className="flex items-center gap-2">
                  <span className="inline-block h-3 w-3 rounded-full" style={{ background: c.color }} />
                  <span className="font-medium text-ink">{c.name}</span>
                  <span className="font-normal text-ink-soft">· {c.year}{typeof c.days_count === "number" ? ` · ${c.days_count} días asignados` : ""}</span>
                </span>
                <button onClick={() => setSelected(c)} className="text-xs font-medium text-primary hover:underline">Gestionar días</button>
              </li>
            ))}
          </ul>
        )}
        <div className="flex flex-wrap items-end gap-3">
          <TextField label="Nombre" value={calForm.name} onChange={(v) => setCalForm((p) => ({ ...p, name: v }))} className="w-44" />
          <Field label="Color"><input type="color" value={calForm.color} onChange={(e) => setCalForm((p) => ({ ...p, color: e.target.value }))} className="h-9 w-16 rounded border border-line bg-canvas" /></Field>
          <Button variant="secondary" onClick={createCalendar} disabled={!calForm.name}>Añadir calendario</Button>
        </div>
      </Card>

      {selected && (
        <CalendarDetail
          calendarId={selected.id}
          calendarName={selected.name}
          year={selected.year}
          templates={templates}
          onClose={() => setSelected(null)}
          onChanged={() => { void load(); }}
        />
      )}
    </div>
  );
}
