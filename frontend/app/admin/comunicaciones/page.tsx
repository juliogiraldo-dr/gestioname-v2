"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import {
  Badge,
  Button,
  Card,
  EmptyState,
  Field,
  PageHeader,
  SelectField,
  Skeleton,
  TextField,
  Toggle,
} from "@/components/ui";

type Entity = { id: string; name: string };
type Company = { id: string; name: string };
type MemberType = { id: string; name: string };
type Communication = {
  id: string;
  audience: string;
  entity: string | null;
  subject: string;
  recipients_count: number;
  trigger: string;
  sent_by: string | null;
  created_at: string | null;
};

const STATUS_OPTS: ReadonlyArray<readonly [string, string]> = [
  ["", "Todos los estados"],
  ["activo", "Activos"],
  ["honor", "De honor"],
  ["baja_impagada", "Baja por impago"],
  ["baja_voluntaria", "Baja voluntaria"],
];
const PAYMENT_OPTS: ReadonlyArray<readonly [string, string]> = [
  ["", "Cualquier estado de pago"],
  ["pagado", "Cuota pagada"],
  ["pendiente", "Cuota pendiente"],
];

type Tab = "socios" | "empleados" | "recordatorios";

export default function ComunicacionesPage() {
  const [tab, setTab] = useState<Tab>("socios");

  return (
    <div>
      <PageHeader title="Comunicaciones" subtitle="Email masivo a socios y empleados, y recordatorios de cuota" />

      <div className="mb-6 flex gap-1 border-b border-line">
        {([
          ["socios", "Email a socios"],
          ["empleados", "Email a empleados"],
          ["recordatorios", "Recordatorios de cuota"],
        ] as const).map(([key, label]) => (
          <button
            key={key}
            onClick={() => setTab(key)}
            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
              tab === key ? "border-secondary text-primary" : "border-transparent text-ink-soft hover:text-ink"
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {tab === "socios" && <SociosTab />}
      {tab === "empleados" && <EmpleadosTab />}
      {tab === "recordatorios" && <RecordatoriosTab />}

      <History key={tab} />
    </div>
  );
}

function useEntities() {
  const [entities, setEntities] = useState<Entity[]>([]);
  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Entity[] }>("/entities");
        setEntities(res.data);
      } catch {
        setEntities([]);
      }
    })();
  }, []);
  return entities;
}

// --------------------------------------------------------------- Socios ------

function SociosTab() {
  const toast = useToast();
  const entities = useEntities();
  const [entityId, setEntityId] = useState("");
  const [types, setTypes] = useState<MemberType[]>([]);
  const [status, setStatus] = useState("");
  const [typeId, setTypeId] = useState("");
  const [payment, setPayment] = useState("");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");
  const [count, setCount] = useState<number | null>(null);
  const [sending, setSending] = useState(false);

  useEffect(() => {
    if (!entityId) return;
    void (async () => {
      try {
        const res = await api<{ data: MemberType[] }>(`/entities/${entityId}/member-types`);
        setTypes(res.data);
      } catch {
        setTypes([]);
      }
    })();
  }, [entityId]);

  const filterParams = useCallback(() => {
    const p = new URLSearchParams();
    if (status) p.set("status", status);
    if (typeId) p.set("member_type_id", typeId);
    if (payment) p.set("payment", payment);
    return p;
  }, [status, typeId, payment]);

  async function preview() {
    if (!entityId) return;
    try {
      const res = await api<{ data: { count: number } }>(`/entities/${entityId}/communications/preview-socios?${filterParams()}`);
      setCount(res.data.count);
    } catch {
      setCount(null);
    }
  }

  async function send() {
    if (!entityId || !subject.trim() || !body.trim()) {
      toast.warning("Selecciona entidad y completa asunto y cuerpo.");
      return;
    }
    setSending(true);
    try {
      const res = await api<{ data: { sent: number } }>(`/entities/${entityId}/communications/socios`, {
        method: "POST",
        body: { subject, body, status: status || null, member_type_id: typeId || null, payment: payment || null },
      });
      toast.success(`Email enviado a ${res.data.sent} socio(s).`);
      setSubject("");
      setBody("");
      setCount(null);
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo enviar.");
    } finally {
      setSending(false);
    }
  }

  if (entities.length === 0) {
    return <EmptyState title="Sin entidades" message="Crea una entidad para enviar comunicaciones a sus socios." />;
  }

  return (
    <Card className="space-y-4 p-5">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <SelectField
          label="Entidad"
          value={entityId}
          onChange={(v) => { setEntityId(v); setCount(null); }}
          options={[["", "Selecciona…"], ...entities.map((e) => [e.id, e.name] as const)]}
        />
        <SelectField label="Estado" value={status} onChange={(v) => { setStatus(v); setCount(null); }} options={STATUS_OPTS} />
        <SelectField
          label="Tipo de socio"
          value={typeId}
          onChange={(v) => { setTypeId(v); setCount(null); }}
          options={[["", "Todos"], ...types.map((t) => [t.id, t.name] as const)]}
        />
        <SelectField label="Pago" value={payment} onChange={(v) => { setPayment(v); setCount(null); }} options={PAYMENT_OPTS} />
      </div>

      <TextField label="Asunto" value={subject} onChange={setSubject} />
      <Field label="Mensaje">
        <textarea
          value={body}
          onChange={(e) => setBody(e.target.value)}
          rows={6}
          className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
          placeholder="Escribe el mensaje. Cada línea será un párrafo."
        />
      </Field>

      <div className="flex items-center justify-between gap-4">
        <div className="text-sm text-ink-soft">
          <Button variant="ghost" onClick={preview} disabled={!entityId}>Vista previa</Button>
          {count !== null && <span className="ml-2">Llegaría a <strong>{count}</strong> socio(s) con email.</span>}
        </div>
        <Button onClick={send} disabled={sending}>{sending ? "Enviando…" : "Enviar"}</Button>
      </div>
    </Card>
  );
}

// ------------------------------------------------------------- Empleados -----

function EmpleadosTab() {
  const toast = useToast();
  const [companies, setCompanies] = useState<Company[]>([]);
  const [companyId, setCompanyId] = useState("");
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");
  const [count, setCount] = useState<number | null>(null);
  const [sending, setSending] = useState(false);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Company[] }>("/companies");
        setCompanies(res.data);
      } catch {
        setCompanies([]);
      }
    })();
  }, []);

  async function preview() {
    try {
      const q = companyId ? `?company_id=${companyId}` : "";
      const res = await api<{ data: { count: number } }>(`/communications/preview-empleados${q}`);
      setCount(res.data.count);
    } catch {
      setCount(null);
    }
  }

  async function send() {
    if (!subject.trim() || !body.trim()) {
      toast.warning("Completa asunto y cuerpo.");
      return;
    }
    setSending(true);
    try {
      const res = await api<{ data: { sent: number } }>("/communications/empleados", {
        method: "POST",
        body: { subject, body, company_id: companyId || null },
      });
      toast.success(`Email enviado a ${res.data.sent} empleado(s).`);
      setSubject("");
      setBody("");
      setCount(null);
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo enviar.");
    } finally {
      setSending(false);
    }
  }

  return (
    <Card className="space-y-4 p-5">
      <SelectField
        label="Empresa"
        value={companyId}
        onChange={(v) => { setCompanyId(v); setCount(null); }}
        options={[["", "Todas las empresas"], ...companies.map((c) => [c.id, c.name] as const)]}
        className="max-w-sm"
      />
      <TextField label="Asunto" value={subject} onChange={setSubject} />
      <Field label="Mensaje">
        <textarea
          value={body}
          onChange={(e) => setBody(e.target.value)}
          rows={6}
          className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
          placeholder="Escribe el mensaje. Cada línea será un párrafo."
        />
      </Field>
      <div className="flex items-center justify-between gap-4">
        <div className="text-sm text-ink-soft">
          <Button variant="ghost" onClick={preview}>Vista previa</Button>
          {count !== null && <span className="ml-2">Llegaría a <strong>{count}</strong> empleado(s) con email.</span>}
        </div>
        <Button onClick={send} disabled={sending}>{sending ? "Enviando…" : "Enviar"}</Button>
      </div>
    </Card>
  );
}

// ----------------------------------------------------------- Recordatorios ---

type Reminder = { enabled: boolean; days_before: number; subject: string; body: string };

function RecordatoriosTab() {
  const toast = useToast();
  const entities = useEntities();
  const [entityId, setEntityId] = useState("");
  const [settings, setSettings] = useState<Reminder | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let active = true;
    void (async () => {
      if (!entityId) {
        if (active) setSettings(null);
        return;
      }
      try {
        const res = await api<{ data: Reminder }>(`/entities/${entityId}/quota-reminder`);
        if (active) setSettings(res.data);
      } catch {
        if (active) setSettings(null);
      }
    })();
    return () => {
      active = false;
    };
  }, [entityId]);

  async function save() {
    if (!settings) return;
    setSaving(true);
    try {
      await api(`/entities/${entityId}/quota-reminder`, { method: "PUT", body: settings });
      toast.success("Recordatorio guardado.");
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo guardar.");
    } finally {
      setSaving(false);
    }
  }

  if (entities.length === 0) {
    return <EmptyState title="Sin entidades" message="Crea una entidad para configurar sus recordatorios de cuota." />;
  }

  return (
    <Card className="space-y-4 p-5">
      <SelectField
        label="Entidad"
        value={entityId}
        onChange={setEntityId}
        options={[["", "Selecciona…"], ...entities.map((e) => [e.id, e.name] as const)]}
        className="max-w-sm"
      />

      {settings && (
        <>
          <Toggle
            on={settings.enabled}
            onClick={() => setSettings({ ...settings, enabled: !settings.enabled })}
            label="Enviar recordatorio automático de cuota"
          />
          <p className="text-xs text-ink-soft">
            Se envía a los socios con la cuota pendiente, los días indicados antes del cierre del ejercicio (31/dic).
          </p>
          <TextField
            label="Días antes del cierre"
            type="number"
            value={String(settings.days_before)}
            onChange={(v) => setSettings({ ...settings, days_before: Number(v) || 0 })}
            className="max-w-xs"
          />
          <TextField label="Asunto" value={settings.subject} onChange={(v) => setSettings({ ...settings, subject: v })} />
          <Field label="Mensaje">
            <textarea
              value={settings.body}
              onChange={(e) => setSettings({ ...settings, body: e.target.value })}
              rows={5}
              className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
            />
          </Field>
          <div className="flex justify-end">
            <Button onClick={save} disabled={saving}>{saving ? "Guardando…" : "Guardar"}</Button>
          </div>
        </>
      )}
    </Card>
  );
}

// --------------------------------------------------------------- Historial ---

function History() {
  const [items, setItems] = useState<Communication[] | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Communication[] }>("/communications");
        setItems(res.data);
      } catch {
        setItems([]);
      }
    })();
  }, []);

  return (
    <div className="mt-8">
      <h2 className="mb-3 text-sm font-semibold text-primary">Historial</h2>
      {items === null ? (
        <Skeleton rows={3} />
      ) : items.length === 0 ? (
        <p className="text-sm text-ink-soft">Aún no se ha enviado ninguna comunicación.</p>
      ) : (
        <Card className="divide-y divide-line">
          {items.map((c) => (
            <div key={c.id} className="flex items-center justify-between gap-4 p-4">
              <div>
                <p className="font-medium text-ink">{c.subject}</p>
                <p className="text-xs text-ink-soft">
                  {c.entity ?? (c.audience === "empleados" ? "Empleados" : "Socios")}
                  {c.sent_by ? ` · ${c.sent_by}` : ""}
                  {c.created_at ? ` · ${new Date(c.created_at).toLocaleString("es-ES")}` : ""}
                </p>
              </div>
              <div className="flex items-center gap-2">
                {c.trigger === "recordatorio_cuota" && <Badge tone="info">Recordatorio</Badge>}
                <Badge tone="ok">{c.recipients_count} dest.</Badge>
              </div>
            </div>
          ))}
        </Card>
      )}
    </div>
  );
}
