"use client";

import { useCallback, useEffect, useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { api, ApiError, downloadFile, uploadFile } from "@/lib/api";
import { useActiveCompany } from "@/lib/company";
import { useToast } from "@/lib/toast";
import { Avatar, Badge, Button, Card, EmptyState, Modal, PageHeader, Pagination, type Paginated, SelectField, Skeleton, Spinner, TextField } from "@/components/ui";

type Company = { id: string; name: string };
type Employee = {
  id: string;
  full_name: string;
  dni: string | null;
  department: string | null;
  job_position: string | null;
  employment_status: string | null;
  active: boolean;
  company_id: string;
};

const ESTADOS = [["", "Todos"], ["1", "Activos"], ["0", "Inactivos"]] as const;

export default function EmpleadosPage() {
  const company = useActiveCompany();
  const companies = company?.companies ?? [];
  const companyId = company?.activeId ?? "";
  const queryClient = useQueryClient();
  const [department, setDepartment] = useState("");
  const [estado, setEstado] = useState("");
  const [page, setPage] = useState(1);
  const [selected, setSelected] = useState<string | null>(null);
  const [modal, setModal] = useState<"create" | "invite" | null>(null);

  const query = useQuery({
    queryKey: ["employees", companyId, department, estado, page],
    enabled: !!companyId,
    queryFn: () => {
      const params = new URLSearchParams({ company_id: companyId, page: String(page) });
      if (department) params.set("department", department);
      if (estado) params.set("active", estado);
      return api<Paginated<Employee>>(`/employees?${params}`);
    },
  });

  const refresh = useCallback(() => { void queryClient.invalidateQueries({ queryKey: ["employees"] }); }, [queryClient]);
  const employees = query.data?.data ?? [];

  if (selected) {
    return <EmployeeDetail employeeId={selected} companies={companies} onBack={() => setSelected(null)} onChanged={refresh} />;
  }

  if (company && companies.length === 0) {
    return (
      <div>
        <PageHeader title="Empleados" subtitle="Fichas, altas e incidencias" />
        <Card className="p-8 text-center"><p className="text-sm text-ink-soft">Crea primero una empresa en Configuración.</p></Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Empleados"
        subtitle="Fichas, altas e incidencias"
        action={
          <div className="flex flex-wrap gap-2">
            <Button variant="ghost" onClick={() => void downloadFile(`/employees/export?company_id=${companyId}${estado ? `&active=${estado}` : ""}`, { method: "GET", fallbackName: "empleados.xlsx" })}>Exportar</Button>
            <Button variant="secondary" onClick={() => setModal("invite")}>Invitar</Button>
            <Button onClick={() => setModal("create")}>Nuevo empleado</Button>
          </div>
        }
      />

      <Card className="p-5">
        <div className="flex flex-wrap items-end gap-3">
          <SelectField label="Estado" value={estado} onChange={(v) => { setEstado(v); setPage(1); }} options={ESTADOS} />
          <TextField label="Departamento" value={department} onChange={(v) => { setDepartment(v); setPage(1); }} placeholder="Exacto" />
        </div>
      </Card>

      <Card className="overflow-hidden">
        {query.isLoading ? <Skeleton /> : employees.length === 0 ? (
          <EmptyState
            title="Añade tu primer empleado"
            message="No hay empleados con estos criterios. Da de alta uno nuevo o invítalo por email."
            action={<Button onClick={() => setModal("create")}>Nuevo empleado</Button>}
          />
        ) : (
          <div className="overflow-x-auto">
          <table className="w-full min-w-[560px] text-sm">
            <thead className="border-b border-line bg-canvas text-left text-xs uppercase tracking-wide text-ink-soft">
              <tr><th className="px-5 py-3 font-medium">Empleado</th><th className="px-5 py-3 font-medium">Departamento</th><th className="px-5 py-3 font-medium">Puesto</th><th className="px-5 py-3 font-medium">Estado</th></tr>
            </thead>
            <tbody className="divide-y divide-line">
              {employees.map((e) => (
                <tr key={e.id} onClick={() => setSelected(e.id)} className="cursor-pointer hover:bg-canvas">
                  <td className="px-5 py-3">
                    <div className="flex items-center gap-3">
                      <Avatar name={e.full_name} />
                      <span className="font-medium text-ink">{e.full_name}</span>
                    </div>
                  </td>
                  <td className="px-5 py-3 text-ink-soft">{e.department ?? "—"}</td>
                  <td className="px-5 py-3 text-ink-soft">{e.job_position ?? "—"}</td>
                  <td className="px-5 py-3"><Badge tone={e.active ? "ok" : "neutral"}>{e.active ? "Activo" : "Inactivo"}</Badge></td>
                </tr>
              ))}
            </tbody>
          </table>
          </div>
        )}
      </Card>

      <Pagination meta={query.data?.meta} onPage={setPage} />

      {modal === "create" && <CreateEmployee companies={companies} onClose={() => setModal(null)} onSaved={() => { setModal(null); refresh(); }} />}
      {modal === "invite" && <InviteEmployee companies={companies} onClose={() => setModal(null)} onSaved={() => { setModal(null); refresh(); }} />}
    </div>
  );
}

function CreateEmployee({ companies, onClose, onSaved }: { companies: Company[]; onClose: () => void; onSaved: () => void }) {
  const [form, setForm] = useState({ company_id: companies[0]?.id ?? "", first_name: "", last_name: "", email_personal: "", department: "", job_position: "", clock_code: "" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const toast = useToast();
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));
  const codeError = form.clock_code && !/^\d{8}$/.test(form.clock_code) ? "Deben ser 8 dígitos" : null;

  async function submit() {
    setError(null); setBusy(true);
    try {
      await api("/employees", { method: "POST", body: {
        company_id: form.company_id, first_name: form.first_name, last_name: form.last_name,
        email_personal: form.email_personal || null, department: form.department || null,
        job_position: form.job_position || null, clock_code: form.clock_code || null,
      } });
      toast.success("Empleado creado.");
      onSaved();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo crear"); } finally { setBusy(false); }
  }

  return (
    <Modal title="Nuevo empleado" onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-2">
        <SelectField label="Empresa" value={form.company_id} onChange={(v) => set("company_id", v)} options={companies.map((c) => [c.id, c.name] as const)} />
        <TextField label="Nombre" value={form.first_name} onChange={(v) => set("first_name", v)} />
        <TextField label="Apellidos" value={form.last_name} onChange={(v) => set("last_name", v)} />
        <TextField label="Email personal" value={form.email_personal} onChange={(v) => set("email_personal", v)} />
        <TextField label="Departamento" value={form.department} onChange={(v) => set("department", v)} />
        <TextField label="Puesto" value={form.job_position} onChange={(v) => set("job_position", v)} />
        <div>
          <TextField label="Código fichaje (8 díg.)" value={form.clock_code} onChange={(v) => set("clock_code", v.replace(/\D/g, "").slice(0, 8))} />
          {codeError && <p className="mt-1 text-xs text-red-600">{codeError}</p>}
        </div>
      </div>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.company_id || !form.first_name || !form.last_name || !!codeError}>{busy ? "Creando…" : "Crear"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}

function InviteEmployee({ companies, onClose, onSaved }: { companies: Company[]; onClose: () => void; onSaved: () => void }) {
  const [form, setForm] = useState({ company_id: companies[0]?.id ?? "", first_name: "", last_name: "", email: "" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const toast = useToast();
  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  async function submit() {
    setError(null); setBusy(true);
    try {
      await api("/employees/invite", { method: "POST", body: form });
      toast.success("Invitación enviada por email.");
      onSaved();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo invitar"); } finally { setBusy(false); }
  }

  return (
    <Modal title="Invitar empleado por email" onClose={onClose}>
      <div className="grid gap-3 sm:grid-cols-2">
        <SelectField label="Empresa" value={form.company_id} onChange={(v) => set("company_id", v)} options={companies.map((c) => [c.id, c.name] as const)} />
        <TextField label="Email" value={form.email} onChange={(v) => set("email", v)} />
        <TextField label="Nombre" value={form.first_name} onChange={(v) => set("first_name", v)} />
        <TextField label="Apellidos" value={form.last_name} onChange={(v) => set("last_name", v)} />
      </div>
      <p className="mt-3 text-xs text-ink-soft">Se enviará un enlace mágico de acceso al portal del empleado.</p>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
      <div className="mt-4 flex gap-2">
        <Button onClick={submit} disabled={busy || !form.company_id || !form.email || !form.first_name || !form.last_name}>{busy ? "Enviando…" : "Invitar"}</Button>
        <Button variant="ghost" onClick={onClose}>Cancelar</Button>
      </div>
    </Modal>
  );
}

// ─────────────────────────────────────────── Ficha ───────────────────────────────────────────

type FullEmployee = Record<string, unknown> & {
  id: string; first_name: string; last_name: string; full_name: string; active: boolean; company_id: string;
};
type WorkCenter = { id: string; name: string; agreement_ids?: string[] };
type Agreement = { id: string; name: string };

const TABS = [
  ["personales", "Datos personales"],
  ["laboral", "Laboral"],
  ["formacion", "Formación"],
  ["documentos", "Documentos"],
  ["materiales", "Materiales"],
  ["comportamiento", "Comportamiento"],
] as const;

const PERSONAL_FIELDS: ReadonlyArray<readonly [string, string, string?]> = [
  ["first_name", "Nombre"], ["last_name", "Apellidos"], ["second_last_name", "Segundo apellido"],
  ["dni", "DNI"], ["birth_date", "Fecha nacimiento", "date"], ["nationality", "Nacionalidad"],
  ["email_personal", "Email personal"], ["phone_personal", "Teléfono"], ["address", "Dirección"],
  ["postal_code", "Código postal"], ["city", "Ciudad"], ["province", "Provincia"], ["iban", "IBAN"],
];

const LABORAL_FIELDS: ReadonlyArray<readonly [string, string, string?]> = [
  ["department", "Departamento"], ["job_position", "Puesto"], ["job_category", "Categoría"],
  ["hire_date", "Fecha de alta", "date"], ["clock_code", "Código de fichaje"],
  ["email_company", "Email empresa"], ["phone_company", "Teléfono empresa"],
];

function EmployeeDetail({ employeeId, companies, onBack, onChanged }: { employeeId: string; companies: Company[]; onBack: () => void; onChanged: () => void }) {
  const [emp, setEmp] = useState<FullEmployee | null>(null);
  const [centers, setCenters] = useState<WorkCenter[]>([]);
  const [agreements, setAgreements] = useState<Agreement[]>([]);
  const [tab, setTab] = useState<(typeof TABS)[number][0]>("personales");
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const toast = useToast();

  const load = useCallback(async () => {
    const res = await api<{ data: FullEmployee }>(`/employees/${employeeId}`);
    setEmp(res.data);
    const cid = res.data.company_id;
    const [wc, ag] = await Promise.all([
      api<{ data: WorkCenter[] }>(`/companies/${cid}/work-centers`).catch(() => ({ data: [] })),
      api<{ data: Agreement[] }>(`/agreements?company_id=${cid}`).catch(() => ({ data: [] })),
    ]);
    setCenters(wc.data);
    setAgreements(ag.data);
  }, [employeeId]);

  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  function set(key: string, value: unknown) { setEmp((p) => (p ? { ...p, [key]: value } : p)); }
  const str = (k: string) => (emp && emp[k] != null ? String(emp[k]) : "");

  async function save() {
    if (!emp) return;
    setError(null); setSaving(true);
    try {
      const payload: Record<string, unknown> = {};
      for (const [k] of [...PERSONAL_FIELDS, ...LABORAL_FIELDS]) payload[k] = emp[k] ?? null;
      payload.work_center_id = emp.work_center_id ?? null;
      payload.agreement_id = emp.agreement_id ?? null;
      await api(`/employees/${employeeId}`, { method: "PUT", body: payload });
      toast.success("Cambios guardados.");
      onChanged();
      await load();
    } catch (e) { setError(e instanceof ApiError ? e.message : "No se pudo guardar"); } finally { setSaving(false); }
  }

  async function toggleActive() {
    if (!emp) return;
    await api(`/employees/${employeeId}/${emp.active ? "deactivate" : "activate"}`, { method: "PATCH" });
    toast.info(emp.active ? "Empleado desactivado." : "Empleado activado.");
    onChanged();
    await load();
  }

  if (!emp) return <Spinner />;
  const companyName = companies.find((c) => c.id === emp.company_id)?.name ?? "";

  // Convenios filtrados por el centro asignado: si el centro tiene convenios vinculados,
  // solo se ofrecen esos; si no, todos los de la empresa.
  const centerAgreementIds = centers.find((c) => c.id === emp.work_center_id)?.agreement_ids ?? [];
  const filteredAgreements = centerAgreementIds.length > 0
    ? agreements.filter((a) => centerAgreementIds.includes(a.id))
    : agreements;

  return (
    <div className="space-y-4">
      <button onClick={onBack} className="text-sm text-primary hover:underline">← Volver al listado</button>

      <Card className="p-5">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <h2 className="text-lg font-semibold text-primary">{emp.full_name}</h2>
            <Badge tone={emp.active ? "ok" : "neutral"}>{emp.active ? "Activo" : "Inactivo"}</Badge>
            {companyName && <span className="text-sm text-ink-soft">{companyName}</span>}
          </div>
          <Button variant="secondary" onClick={toggleActive}>{emp.active ? "Desactivar" : "Activar"}</Button>
        </div>

        <div className="mt-4 flex flex-wrap gap-1 border-b border-line">
          {TABS.map(([key, label]) => (
            <button key={key} onClick={() => setTab(key)}
              className={`-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors ${tab === key ? "border-secondary text-primary" : "border-transparent text-ink-soft hover:text-ink"}`}>
              {label}
            </button>
          ))}
        </div>

        <div className="mt-5">
          {tab === "personales" && (
            <FieldGrid fields={PERSONAL_FIELDS} str={str} set={set} />
          )}
          {tab === "laboral" && (
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              {LABORAL_FIELDS.map(([k, label, type]) => (
                <TextField key={k} label={label} type={type ?? "text"} value={str(k)} onChange={(v) => set(k, v)} />
              ))}
              <SelectField label="Centro de trabajo" value={str("work_center_id")} onChange={(v) => set("work_center_id", v || null)}
                options={[["", "Sin asignar"], ...centers.map((c) => [c.id, c.name] as const)]} />
              <SelectField label="Convenio" value={str("agreement_id")} onChange={(v) => set("agreement_id", v || null)}
                options={[["", "Sin asignar"], ...filteredAgreements.map((a) => [a.id, a.name] as const)]} />
              <SelectField label="Situación laboral" value={str("employment_status")} onChange={(v) => set("employment_status", v || null)}
                options={[["", "—"], ["active", "Activo"], ["inactive", "Inactivo"], ["leave", "Baja/Permiso"]]} />
            </div>
          )}
          {tab === "formacion" && <QualificationsTab employeeId={employeeId} />}
          {tab === "documentos" && <DocumentsTab employeeId={employeeId} />}
          {tab === "materiales" && <MaterialsTab employeeId={employeeId} />}
          {tab === "comportamiento" && <BehaviorTab employeeId={employeeId} />}
        </div>

        {(tab === "personales" || tab === "laboral") && (
          <>
            {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
            <div className="mt-4"><Button onClick={save} disabled={saving}>{saving ? "Guardando…" : "Guardar cambios"}</Button></div>
          </>
        )}
      </Card>
    </div>
  );
}

function FieldGrid({ fields, str, set }: {
  fields: ReadonlyArray<readonly [string, string, string?]>;
  str: (k: string) => string;
  set: (k: string, v: unknown) => void;
}) {
  return (
    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      {fields.map(([k, label, type]) => (
        <TextField key={k} label={label} type={type ?? "text"} value={str(k)} onChange={(v) => set(k, v)} />
      ))}
    </div>
  );
}

type Row = Record<string, unknown> & { id: string };

/** Lista genérica con borrado para las sub-fichas. */
function SubList({ rows, render, onDelete }: { rows: Row[]; render: (r: Row) => React.ReactNode; onDelete: (id: string) => void }) {
  if (rows.length === 0) return <p className="mb-4 text-sm text-ink-soft">Sin registros.</p>;
  return (
    <ul className="mb-4 divide-y divide-line">
      {rows.map((r) => (
        <li key={r.id} className="flex items-center justify-between py-2 text-sm">
          <span>{render(r)}</span>
          <button onClick={() => onDelete(r.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
        </li>
      ))}
    </ul>
  );
}

function QualificationsTab({ employeeId }: { employeeId: string }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [form, setForm] = useState({ type: "curso", name: "", institution: "", date_obtained: "", expiry_date: "" });
  const load = useCallback(async () => {
    const res = await api<{ data: Row[] }>(`/employees/${employeeId}/qualifications`);
    setRows(res.data);
  }, [employeeId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function add() {
    await api(`/employees/${employeeId}/qualifications`, { method: "POST", body: {
      type: form.type, name: form.name, institution: form.institution || null,
      date_obtained: form.date_obtained || null, expiry_date: form.expiry_date || null,
    } });
    setForm({ type: "curso", name: "", institution: "", date_obtained: "", expiry_date: "" });
    await load();
  }
  async function del(id: string) { await api(`/qualifications/${id}`, { method: "DELETE" }); await load(); }

  return (
    <div>
      <SubList rows={rows} onDelete={del} render={(r) => (
        <><span className="font-medium text-ink">{String(r.name)}</span> <span className="text-ink-soft">· {String(r.type)}{r.institution ? ` · ${String(r.institution)}` : ""}</span></>
      )} />
      <div className="flex flex-wrap items-end gap-2">
        <SelectField label="Tipo" value={form.type} onChange={(v) => setForm((p) => ({ ...p, type: v }))}
          options={[["titulacion", "Titulación"], ["curso", "Curso"], ["certificado", "Certificado"], ["conocimiento", "Conocimiento"], ["experiencia", "Experiencia"]]} />
        <TextField label="Nombre" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} className="w-48" />
        <TextField label="Centro" value={form.institution} onChange={(v) => setForm((p) => ({ ...p, institution: v }))} className="w-40" />
        <TextField label="Obtención" type="date" value={form.date_obtained} onChange={(v) => setForm((p) => ({ ...p, date_obtained: v }))} />
        <Button variant="secondary" onClick={add} disabled={!form.name}>Añadir</Button>
      </div>
    </div>
  );
}

function MaterialsTab({ employeeId }: { employeeId: string }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [form, setForm] = useState({ name: "", serial_number: "", delivery_date: "", status: "entregado" });
  const load = useCallback(async () => {
    const res = await api<{ data: Row[] }>(`/employees/${employeeId}/materials`);
    setRows(res.data);
  }, [employeeId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function add() {
    await api(`/employees/${employeeId}/materials`, { method: "POST", body: {
      name: form.name, serial_number: form.serial_number || null, delivery_date: form.delivery_date || null, status: form.status,
    } });
    setForm({ name: "", serial_number: "", delivery_date: "", status: "entregado" });
    await load();
  }
  async function del(id: string) { await api(`/materials/${id}`, { method: "DELETE" }); await load(); }

  return (
    <div>
      <SubList rows={rows} onDelete={del} render={(r) => (
        <><span className="font-medium text-ink">{String(r.name)}</span> <Badge tone={r.status === "devuelto" ? "neutral" : r.status === "perdido" ? "warn" : "ok"}>{String(r.status)}</Badge></>
      )} />
      <div className="flex flex-wrap items-end gap-2">
        <TextField label="Material" value={form.name} onChange={(v) => setForm((p) => ({ ...p, name: v }))} className="w-44" />
        <TextField label="Nº serie" value={form.serial_number} onChange={(v) => setForm((p) => ({ ...p, serial_number: v }))} className="w-32" />
        <TextField label="Entrega" type="date" value={form.delivery_date} onChange={(v) => setForm((p) => ({ ...p, delivery_date: v }))} />
        <SelectField label="Estado" value={form.status} onChange={(v) => setForm((p) => ({ ...p, status: v }))}
          options={[["entregado", "Entregado"], ["devuelto", "Devuelto"], ["perdido", "Perdido"]]} />
        <Button variant="secondary" onClick={add} disabled={!form.name}>Añadir</Button>
      </div>
    </div>
  );
}

function BehaviorTab({ employeeId }: { employeeId: string }) {
  const [rows, setRows] = useState<Row[]>([]);
  const today = new Date().toISOString().slice(0, 10);
  const [form, setForm] = useState({ type: "felicitacion", date: today, description: "" });
  const load = useCallback(async () => {
    const res = await api<{ data: Row[] }>(`/employees/${employeeId}/behavior`);
    setRows(res.data);
  }, [employeeId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function add() {
    await api(`/employees/${employeeId}/behavior`, { method: "POST", body: { type: form.type, date: form.date, description: form.description || null } });
    setForm({ type: "felicitacion", date: today, description: "" });
    await load();
  }
  async function del(id: string) { await api(`/behavior/${id}`, { method: "DELETE" }); await load(); }

  return (
    <div>
      <SubList rows={rows} onDelete={del} render={(r) => (
        <><Badge tone={r.type === "felicitacion" ? "ok" : "warn"}>{String(r.type)}</Badge> <span className="text-ink-soft">{String(r.date)}{r.description ? ` · ${String(r.description)}` : ""}</span></>
      )} />
      <div className="flex flex-wrap items-end gap-2">
        <SelectField label="Tipo" value={form.type} onChange={(v) => setForm((p) => ({ ...p, type: v }))}
          options={[["felicitacion", "Felicitación"], ["amonestacion", "Amonestación"], ["sancion", "Sanción"]]} />
        <TextField label="Fecha" type="date" value={form.date} onChange={(v) => setForm((p) => ({ ...p, date: v }))} />
        <TextField label="Descripción" value={form.description} onChange={(v) => setForm((p) => ({ ...p, description: v }))} className="w-64" />
        <Button variant="secondary" onClick={add} disabled={!form.date}>Añadir</Button>
      </div>
    </div>
  );
}

function DocumentsTab({ employeeId }: { employeeId: string }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [file, setFile] = useState<File | null>(null);
  const [name, setName] = useState("");
  const [busy, setBusy] = useState(false);
  const load = useCallback(async () => {
    const res = await api<{ data: Row[] }>(`/employees/${employeeId}/documents`);
    setRows(res.data);
  }, [employeeId]);
  useEffect(() => { void (async () => { await load(); })(); }, [load]);

  async function upload() {
    if (!file) return;
    setBusy(true);
    try {
      await uploadFile(`/employees/${employeeId}/documents`, file, name ? { name } : {});
      setFile(null); setName("");
      await load();
    } finally { setBusy(false); }
  }
  async function download(r: Row) { await downloadFile(`/documents/${r.id}/download`, { method: "GET", fallbackName: String(r.name) }); }
  async function del(id: string) { await api(`/documents/${id}`, { method: "DELETE" }); await load(); }

  return (
    <div>
      {rows.length === 0 ? <p className="mb-4 text-sm text-ink-soft">Sin documentos.</p> : (
        <ul className="mb-4 divide-y divide-line">
          {rows.map((r) => (
            <li key={r.id} className="flex items-center justify-between py-2 text-sm">
              <span className="font-medium text-ink">{String(r.name)} <span className="font-normal text-ink-soft">· {String(r.type)}</span></span>
              <span className="flex gap-3">
                <button onClick={() => download(r)} className="text-xs text-primary hover:underline">Descargar</button>
                <button onClick={() => del(r.id)} className="text-xs text-red-600 hover:underline">Eliminar</button>
              </span>
            </li>
          ))}
        </ul>
      )}
      <div className="flex flex-wrap items-end gap-2">
        <label className="block">
          <span className="mb-1.5 block text-sm font-medium text-ink">Fichero (PDF/imagen)</span>
          <input type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-sm" />
        </label>
        <TextField label="Nombre (opcional)" value={name} onChange={setName} className="w-48" />
        <Button variant="secondary" onClick={upload} disabled={busy || !file}>{busy ? "Subiendo…" : "Subir"}</Button>
      </div>
    </div>
  );
}
