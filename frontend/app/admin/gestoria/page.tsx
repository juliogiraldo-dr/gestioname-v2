"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { api, uploadFile, downloadFile, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { useDebounce } from "@/lib/hooks";
import { formatDateTime } from "@/lib/utils";
import {
  Avatar,
  Badge,
  Button,
  Card,
  EmptyState,
  Field,
  Modal,
  PageHeader,
  SelectField,
  Skeleton,
  TextField,
} from "@/components/ui";

const MESES = [
  "enero", "febrero", "marzo", "abril", "mayo", "junio",
  "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre",
];

type Payslip = {
  id: string;
  month: number;
  year: number;
  period: string;
  original_name: string;
  notified_at: string | null;
  created_at: string | null;
};

type EmployeeRow = {
  id: string;
  full_name: string;
  email: string | null;
  job_position: string | null;
  payslips: Payslip[];
};

type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
};

type Tab = "nominas" | "documentos" | "a3asesor";

export default function GestoriaPage() {
  const [tab, setTab] = useState<Tab>("nominas");

  return (
    <div>
      <PageHeader
        title="Gestoría"
        subtitle="Nóminas, documentos de RRHH y exportación contable"
      />

      <div className="mb-6 flex gap-1 border-b border-line">
        {([
          ["nominas", "Nóminas"],
          ["documentos", "Documentos RRHH"],
          ["a3asesor", "Exportación a3asesor"],
        ] as const).map(([key, label]) => (
          <button
            key={key}
            onClick={() => setTab(key)}
            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
              tab === key
                ? "border-secondary text-primary"
                : "border-transparent text-ink-soft hover:text-ink"
            }`}
          >
            {label}
          </button>
        ))}
      </div>

      {tab === "nominas" && <NominasTab />}
      {tab === "documentos" && <DocumentosTab />}
      {tab === "a3asesor" && <A3asesorTab />}
    </div>
  );
}

// ---------------------------------------------------------------- Nóminas ----

function NominasTab() {
  const toast = useToast();
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const debouncedSearch = useDebounce(search, 300);
  const [result, setResult] = useState<Paginated<EmployeeRow> | null>(null);
  const [reloadKey, setReloadKey] = useState(0);
  const [uploadFor, setUploadFor] = useState<EmployeeRow | null>(null);
  const [linkUrl, setLinkUrl] = useState<{ url: string; expires_at: string } | null>(null);

  const reload = useCallback(() => setReloadKey((k) => k + 1), []);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const params = new URLSearchParams({ page: String(page) });
        if (debouncedSearch.trim()) params.set("search", debouncedSearch.trim());
        const res = await api<Paginated<EmployeeRow>>(`/payslips?${params.toString()}`);
        if (active) setResult(res);
      } catch {
        if (active) setResult({ data: [], current_page: 1, last_page: 1, total: 0 });
      }
    })();
    return () => {
      active = false;
    };
  }, [page, debouncedSearch, reloadKey]);

  async function generateLink(payslip: Payslip) {
    try {
      const res = await api<{ data: { url: string; expires_at: string } }>("/download-tokens", {
        method: "POST",
        body: { payslip_id: payslip.id },
      });
      setLinkUrl(res.data);
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo generar el enlace.");
    }
  }

  if (result === null) return <Skeleton rows={6} />;

  const rows = result.data;

  return (
    <div className="space-y-4">
      <div className="max-w-sm">
        <TextField
          label="Buscar empleado"
          value={search}
          onChange={(v) => {
            setPage(1);
            setSearch(v);
          }}
          placeholder="Nombre o apellidos"
        />
      </div>

      {rows.length === 0 ? (
        <EmptyState title="Sin empleados" message="No hay empleados que coincidan con la búsqueda." />
      ) : (
        <Card className="divide-y divide-line">
          {rows.map((emp) => (
            <div key={emp.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
              <div className="flex items-start gap-3">
                <Avatar name={emp.full_name} />
                <div>
                  <p className="font-medium text-ink">{emp.full_name}</p>
                  <p className="text-xs text-ink-soft">{emp.email ?? "Sin email"}</p>
                  <div className="mt-2 flex flex-wrap gap-1.5">
                    {emp.payslips.length === 0 ? (
                      <span className="text-xs text-ink-soft">Sin nóminas</span>
                    ) : (
                      emp.payslips.map((p) => (
                        <span key={p.id} className="inline-flex items-center gap-1.5 rounded-full bg-line/60 px-2 py-1 text-xs">
                          <span className="capitalize">{p.period}</span>
                          <button
                            onClick={() => downloadFile(`/payslips/${p.id}/download`, { method: "GET", fallbackName: p.original_name })}
                            className="text-primary hover:underline"
                            title="Descargar"
                          >
                            ↓
                          </button>
                          <button
                            onClick={() => generateLink(p)}
                            className="text-primary hover:underline"
                            title="Generar enlace de descarga"
                          >
                            🔗
                          </button>
                        </span>
                      ))
                    )}
                  </div>
                </div>
              </div>
              <Button variant="secondary" onClick={() => setUploadFor(emp)}>
                Subir nómina
              </Button>
            </div>
          ))}
        </Card>
      )}

      {result && result.last_page > 1 && (
        <div className="flex items-center justify-between text-sm">
          <span className="text-ink-soft">
            Página {result.current_page} de {result.last_page} · {result.total} empleados
          </span>
          <div className="flex gap-2">
            <Button variant="ghost" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
              Anterior
            </Button>
            <Button variant="ghost" disabled={page >= result.last_page} onClick={() => setPage((p) => p + 1)}>
              Siguiente
            </Button>
          </div>
        </div>
      )}

      {uploadFor && (
        <UploadModal
          employee={uploadFor}
          onClose={() => setUploadFor(null)}
          onDone={() => {
            setUploadFor(null);
            reload();
          }}
        />
      )}

      {linkUrl && <LinkModal data={linkUrl} onClose={() => setLinkUrl(null)} />}
    </div>
  );
}

function UploadModal({
  employee,
  onClose,
  onDone,
}: {
  employee: EmployeeRow;
  onClose: () => void;
  onDone: () => void;
}) {
  const toast = useToast();
  const now = new Date();
  const [month, setMonth] = useState(String(now.getMonth() + 1));
  const [year, setYear] = useState(String(now.getFullYear()));
  const [file, setFile] = useState<File | null>(null);
  const [saving, setSaving] = useState(false);

  async function submit() {
    if (!file) {
      toast.warning("Selecciona el PDF de la nómina.");
      return;
    }
    setSaving(true);
    try {
      const res = await uploadFile<{ data: { notified: boolean } }>(
        `/employees/${employee.id}/payslips`,
        file,
        { month, year },
      );
      toast.success(
        res.data.notified
          ? "Nómina subida. Se ha avisado al empleado por email."
          : "Nómina subida (el empleado no tiene email para el aviso).",
      );
      onDone();
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo subir la nómina.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <Modal title={`Subir nómina · ${employee.full_name}`} onClose={onClose}>
      <div className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <SelectField
            label="Mes"
            value={month}
            onChange={setMonth}
            options={MESES.map((m, i) => [String(i + 1), m.charAt(0).toUpperCase() + m.slice(1)] as const)}
          />
          <TextField label="Año" value={year} onChange={setYear} type="number" />
        </div>
        <Field label="Archivo PDF">
          <input
            type="file"
            accept="application/pdf"
            onChange={(e) => setFile(e.target.files?.[0] ?? null)}
            className="block w-full text-sm text-ink-soft file:mr-3 file:rounded-[var(--radius-fluent)] file:border-0 file:bg-secondary/15 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary"
          />
        </Field>
        <div className="flex justify-end gap-2">
          <Button variant="ghost" onClick={onClose}>Cancelar</Button>
          <Button onClick={submit} disabled={saving}>{saving ? "Subiendo…" : "Subir y avisar"}</Button>
        </div>
      </div>
    </Modal>
  );
}

function LinkModal({ data, onClose }: { data: { url: string; expires_at: string }; onClose: () => void }) {
  const toast = useToast();
  const expires = formatDateTime(data.expires_at);

  return (
    <Modal title="Enlace de descarga" onClose={onClose}>
      <div className="space-y-4">
        <p className="text-sm text-ink-soft">
          Enlace de un solo uso, válido hasta el <strong>{expires}</strong>. Compártelo con la gestoría;
          no requiere acceso a la plataforma.
        </p>
        <div className="flex gap-2">
          <input
            readOnly
            value={data.url}
            className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm"
            onFocus={(e) => e.target.select()}
          />
          <Button
            onClick={() => {
              void navigator.clipboard.writeText(data.url);
              toast.success("Enlace copiado.");
            }}
          >
            Copiar
          </Button>
        </div>
      </div>
    </Modal>
  );
}

// ----------------------------------------------------------- Documentos RRHH -

function DocumentosTab() {
  return (
    <Card className="p-6">
      <p className="mb-4 text-sm text-ink-soft">
        Los informes de RRHH (registro horario ET 34.9, informe diario y resumen de ausencias) se
        generan y descargan desde el módulo de Informes, con sus filtros de fechas, empresa y empleados.
      </p>
      <Link href="/admin/informes">
        <Button>Ir a Informes</Button>
      </Link>
    </Card>
  );
}

// --------------------------------------------------------------- a3asesor ----

function A3asesorTab() {
  return (
    <EmptyState
      title="Exportación a a3asesor (suenlace.dat)"
      message="La generación del fichero contable para a3asesor Eco/Con estará disponible en la Fase 3 (Contabilidad)."
      action={<Badge tone="info">Disponible en Fase 3</Badge>}
    />
  );
}
