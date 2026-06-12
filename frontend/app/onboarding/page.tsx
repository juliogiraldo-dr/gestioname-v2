"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api, ApiError } from "@/lib/api";
import { useDebounce } from "@/lib/hooks";
import { Button, Card, Field, TextField } from "@/components/ui";
import { BuildingIcon, EntityIcon, UsersIcon, CheckIcon } from "@/components/icons";

type OrgType = "empresa" | "entidad" | "ambas";

type Draft = {
  type: OrgType | "";
  name: string;
  cif: string; // solo local, no se envía
  city: string; // solo local, no se envía
  subdomain: string;
  admin_email: string;
  terms_accepted: boolean;
};

const EMPTY_DRAFT: Draft = {
  type: "",
  name: "",
  cif: "",
  city: "",
  subdomain: "",
  admin_email: "",
  terms_accepted: false,
};

const STORAGE_KEY = "gm_onboarding";
const TOTAL_STEPS = 5;

const TYPE_OPTIONS: ReadonlyArray<{
  value: OrgType;
  title: string;
  desc: string;
  Icon: (p: { className?: string }) => React.ReactNode;
}> = [
  { value: "empresa", title: "Empresa", desc: "Gestiona empleados, fichajes y nóminas.", Icon: BuildingIcon },
  { value: "entidad", title: "Asociación", desc: "Gestiona socios, cuotas y la entidad.", Icon: EntityIcon },
  { value: "ambas", title: "Ambas", desc: "Empresa y asociación en un mismo espacio.", Icon: UsersIcon },
];

/** Carga el borrador guardado de localStorage de forma segura. */
function readDraft(): Draft {
  if (typeof window === "undefined") return EMPTY_DRAFT;
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return EMPTY_DRAFT;
    const parsed = JSON.parse(raw) as Partial<Draft>;
    return { ...EMPTY_DRAFT, ...parsed };
  } catch {
    return EMPTY_DRAFT;
  }
}

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export default function OnboardingPage() {
  // Inicializador perezoso: evita setState síncrono dentro de useEffect.
  const [draft, setDraft] = useState<Draft>(() => readDraft());
  const [step, setStep] = useState(1);
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [done, setDone] = useState<{ url: string; subdomain: string } | null>(null);

  // Comprobación de disponibilidad del subdominio.
  const [check, setCheck] = useState<{ valid: boolean; available: boolean } | null>(null);
  const debouncedSub = useDebounce(draft.subdomain, 350);

  // Persiste el borrador en cada cambio (no se ejecuta setState aquí).
  useEffect(() => {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));
    } catch {
      // localStorage puede no estar disponible; el flujo sigue funcionando en memoria.
    }
  }, [draft]);

  useEffect(() => {
    if (!debouncedSub) {
      return;
    }
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: { valid: boolean; available: boolean } }>(
          `/register/check-subdomain?subdomain=${encodeURIComponent(debouncedSub)}`,
          { auth: false },
        );
        if (active) setCheck(res.data);
      } catch {
        if (active) setCheck(null);
      }
    })();
    return () => {
      active = false;
    };
  }, [debouncedSub]);

  function set<K extends keyof Draft>(key: K, value: Draft[K]): void {
    setDraft((prev) => ({ ...prev, [key]: value }));
  }

  const subStatus = !draft.subdomain
    ? null
    : draft.subdomain !== debouncedSub || check === null
      ? { text: "Comprobando…", ok: false }
      : !check.valid
        ? { text: "Subdominio no válido", ok: false }
        : check.available
          ? { text: "✓ Disponible", ok: true }
          : { text: "No disponible", ok: false };

  const stepValid: Record<number, boolean> = {
    1: draft.type !== "",
    2: draft.name.trim().length > 0 && check?.valid === true && check?.available === true,
    3: true,
    4: EMAIL_RE.test(draft.admin_email) && draft.terms_accepted,
    5: true,
  };

  function next(): void {
    if (step < TOTAL_STEPS && stepValid[step]) setStep((s) => s + 1);
  }
  function back(): void {
    if (step > 1) setStep((s) => s - 1);
  }

  async function submit(): Promise<void> {
    if (draft.type === "") return;
    setGeneralError(null);
    setErrors({});
    setBusy(true);
    try {
      const res = await api<{ data: { url: string; subdomain: string } }>("/register", {
        method: "POST",
        auth: false,
        body: {
          name: draft.name.trim(),
          type: draft.type,
          subdomain: draft.subdomain.toLowerCase(),
          admin_email: draft.admin_email.trim(),
          terms_accepted: true,
        },
      });
      try {
        window.localStorage.removeItem(STORAGE_KEY);
      } catch {
        // sin persistencia: nada que limpiar
      }
      setDone(res.data);
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) setErrors(err.errors);
        setGeneralError(err.message);
      } else {
        setGeneralError("No se pudo crear la cuenta. Inténtalo de nuevo.");
      }
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="min-h-full">
      <header className="flex items-center justify-between px-6 py-5 sm:px-10">
        <Link href="/" className="text-lg font-semibold text-primary">
          Gestioname
        </Link>
        <Link href="/login" className="text-sm font-medium text-primary hover:underline">
          Iniciar sesión
        </Link>
      </header>

      <section className="mx-auto max-w-2xl px-6 py-6 sm:px-10 sm:py-10">
        {done ? (
          <SuccessPanel url={done.url} subdomain={done.subdomain} />
        ) : (
          <>
            <ProgressBar step={step} />

            <Card className="mt-6 p-6 sm:p-8">
              {step === 1 && (
                <StepType
                  selected={draft.type}
                  onSelect={(t) => set("type", t)}
                />
              )}

              {step === 2 && (
                <StepBasics
                  draft={draft}
                  set={set}
                  subStatus={subStatus}
                  errors={errors}
                />
              )}

              {step === 3 && draft.type !== "" && <StepPreview type={draft.type} name={draft.name} />}

              {step === 4 && (
                <StepAdmin draft={draft} set={set} errors={errors} />
              )}

              {step === 5 && draft.type !== "" && (
                <StepReview draft={draft} type={draft.type} />
              )}

              {generalError && (
                <p className="mt-5 rounded-[var(--radius-fluent)] bg-red-50 px-3 py-2 text-sm text-red-700">
                  {generalError}
                </p>
              )}

              <div className="mt-8 flex items-center justify-between gap-3">
                <Button variant="ghost" onClick={back} disabled={step === 1 || busy}>
                  Atrás
                </Button>
                {step < TOTAL_STEPS ? (
                  <Button onClick={next} disabled={!stepValid[step]}>
                    Siguiente
                  </Button>
                ) : (
                  <Button onClick={submit} disabled={busy}>
                    {busy ? "Creando…" : "Crear cuenta gratis"}
                  </Button>
                )}
              </div>
            </Card>
          </>
        )}
      </section>
    </main>
  );
}

function ProgressBar({ step }: { step: number }) {
  const pct = (step / TOTAL_STEPS) * 100;
  return (
    <div>
      <div className="flex items-center justify-between text-sm">
        <span className="font-medium text-primary">Paso {step} de {TOTAL_STEPS}</span>
        <span className="text-ink-soft">{Math.round(pct)}%</span>
      </div>
      <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-line">
        <div
          className="h-full rounded-full bg-accent transition-all"
          style={{ width: `${pct}%` }}
        />
      </div>
    </div>
  );
}

function StepType({ selected, onSelect }: { selected: OrgType | ""; onSelect: (t: OrgType) => void }) {
  return (
    <div>
      <h2 className="text-xl font-semibold text-primary">¿Qué vas a gestionar?</h2>
      <p className="mt-1 text-sm text-ink-soft">Elige el tipo de organización. Podrás añadir más adelante.</p>
      <div className="mt-6 grid gap-4 sm:grid-cols-3">
        {TYPE_OPTIONS.map(({ value, title, desc, Icon }) => {
          const active = selected === value;
          return (
            <button
              key={value}
              type="button"
              onClick={() => onSelect(value)}
              className={`flex flex-col items-start gap-3 rounded-[var(--radius-fluent)] border p-5 text-left transition-colors ${
                active
                  ? "border-secondary bg-secondary/10 ring-2 ring-secondary/30"
                  : "border-line bg-canvas hover:border-secondary/60"
              }`}
            >
              <span
                className={`flex h-10 w-10 items-center justify-center rounded-[var(--radius-fluent)] ${
                  active ? "bg-secondary/20 text-primary" : "bg-surface text-ink-soft"
                }`}
              >
                <Icon className="h-6 w-6" />
              </span>
              <span className="font-semibold text-ink">{title}</span>
              <span className="text-xs text-ink-soft">{desc}</span>
            </button>
          );
        })}
      </div>
    </div>
  );
}

function StepBasics({
  draft,
  set,
  subStatus,
  errors,
}: {
  draft: Draft;
  set: <K extends keyof Draft>(key: K, value: Draft[K]) => void;
  subStatus: { text: string; ok: boolean } | null;
  errors: Record<string, string[]>;
}) {
  return (
    <div>
      <h2 className="text-xl font-semibold text-primary">Datos básicos</h2>
      <p className="mt-1 text-sm text-ink-soft">Cuéntanos cómo se llama tu organización.</p>
      <div className="mt-6 space-y-4">
        <TextField label="Nombre de la organización" value={draft.name} onChange={(v) => set("name", v)} placeholder="Mi Empresa S.L." />
        <FieldError name="name" errors={errors} />

        <div className="grid gap-4 sm:grid-cols-2">
          <TextField label="CIF (opcional)" value={draft.cif} onChange={(v) => set("cif", v)} placeholder="B12345678" />
          <TextField label="Ciudad (opcional)" value={draft.city} onChange={(v) => set("city", v)} placeholder="Madrid" />
        </div>

        <Field label="Subdominio">
          <div className="flex items-center gap-2">
            <input
              value={draft.subdomain}
              onChange={(e) => set("subdomain", e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ""))}
              placeholder="miempresa"
              className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
            />
            <span className="whitespace-nowrap text-sm text-ink-soft">.gestioname.app</span>
          </div>
          {subStatus && (
            <span className={`mt-1 block text-xs ${subStatus.ok ? "text-[#0d6b50]" : "text-ink-soft"}`}>{subStatus.text}</span>
          )}
        </Field>
        <FieldError name="subdomain" errors={errors} />
      </div>
    </div>
  );
}

function StepPreview({ type, name }: { type: OrgType; name: string }) {
  const orgName = name.trim() || "Tu organización";
  const blocks: Array<{ icon: React.ReactNode; label: string; detail: string }> = [];
  if (type === "empresa" || type === "ambas") {
    blocks.push({
      icon: <BuildingIcon className="h-6 w-6" />,
      label: `Empresa: ${orgName}`,
      detail: "Crearemos tu primera empresa para gestionar empleados, fichajes y nóminas.",
    });
  }
  if (type === "entidad" || type === "ambas") {
    blocks.push({
      icon: <EntityIcon className="h-6 w-6" />,
      label: `Asociación: ${orgName}`,
      detail: "Crearemos tu primera entidad para gestionar socios y cuotas.",
    });
  }
  return (
    <div>
      <h2 className="text-xl font-semibold text-primary">Esto es lo que prepararemos</h2>
      <p className="mt-1 text-sm text-ink-soft">Usaremos el nombre de tu organización para el primer espacio.</p>
      <div className="mt-6 space-y-3">
        {blocks.map((b, i) => (
          <div key={i} className="flex items-start gap-3 rounded-[var(--radius-fluent)] border border-line bg-canvas p-4">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-[var(--radius-fluent)] bg-secondary/20 text-primary">
              {b.icon}
            </span>
            <div>
              <p className="font-medium text-ink">{b.label}</p>
              <p className="mt-0.5 text-sm text-ink-soft">{b.detail}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function StepAdmin({
  draft,
  set,
  errors,
}: {
  draft: Draft;
  set: <K extends keyof Draft>(key: K, value: Draft[K]) => void;
  errors: Record<string, string[]>;
}) {
  return (
    <div>
      <h2 className="text-xl font-semibold text-primary">Administrador</h2>
      <p className="mt-1 text-sm text-ink-soft">Enviaremos un enlace de acceso a este email.</p>
      <div className="mt-6 space-y-4">
        <TextField
          label="Email del administrador"
          type="email"
          value={draft.admin_email}
          onChange={(v) => set("admin_email", v)}
          placeholder="admin@miempresa.com"
        />
        <FieldError name="admin_email" errors={errors} />

        <label className="flex items-start gap-3 text-sm text-ink">
          <input
            type="checkbox"
            checked={draft.terms_accepted}
            onChange={(e) => set("terms_accepted", e.target.checked)}
            className="mt-0.5 h-4 w-4 shrink-0 rounded border-line text-primary focus:ring-secondary/30"
          />
          <span>
            Acepto los{" "}
            <a href="/legal/terminos" target="_blank" rel="noreferrer" className="text-primary hover:underline">
              términos del servicio
            </a>{" "}
            y la{" "}
            <a href="/legal/privacidad" target="_blank" rel="noreferrer" className="text-primary hover:underline">
              política de privacidad
            </a>
            .
          </span>
        </label>
        <FieldError name="terms_accepted" errors={errors} />
      </div>
    </div>
  );
}

const TYPE_LABEL: Record<OrgType, string> = {
  empresa: "Empresa",
  entidad: "Asociación",
  ambas: "Empresa y asociación",
};

function StepReview({ draft, type }: { draft: Draft; type: OrgType }) {
  const rows: Array<[string, string]> = [
    ["Tipo", TYPE_LABEL[type]],
    ["Organización", draft.name.trim() || "—"],
    ["Subdominio", `${draft.subdomain || "—"}.gestioname.app`],
    ["Administrador", draft.admin_email.trim() || "—"],
  ];
  if (draft.cif.trim()) rows.push(["CIF", draft.cif.trim()]);
  if (draft.city.trim()) rows.push(["Ciudad", draft.city.trim()]);

  return (
    <div>
      <h2 className="text-xl font-semibold text-primary">Revisa y crea tu cuenta</h2>
      <p className="mt-1 text-sm text-ink-soft">Comprueba que todo es correcto antes de continuar.</p>
      <dl className="mt-6 divide-y divide-line rounded-[var(--radius-fluent)] border border-line">
        {rows.map(([label, value]) => (
          <div key={label} className="flex items-center justify-between gap-4 px-4 py-3 text-sm">
            <dt className="text-ink-soft">{label}</dt>
            <dd className="text-right font-medium text-ink">{value}</dd>
          </div>
        ))}
      </dl>
    </div>
  );
}

function SuccessPanel({ url, subdomain }: { url: string; subdomain: string }) {
  return (
    <Card className="p-8 text-center">
      <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent/20 text-primary">
        <CheckIcon className="h-7 w-7" />
      </div>
      <h2 className="text-2xl font-semibold text-primary">¡Listo!</h2>
      <p className="mx-auto mt-2 max-w-md text-sm text-ink-soft">
        Hemos creado tu espacio <span className="font-medium text-ink">{subdomain}.gestioname.app</span> y te hemos
        enviado un enlace de acceso. Revisa tu email para entrar por primera vez.
      </p>
      <div className="mt-6 flex flex-col items-center gap-3">
        <a
          href={url}
          className="inline-flex items-center justify-center rounded-[var(--radius-fluent)] bg-primary px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-600"
        >
          Ir a mi espacio
        </a>
        <Link href="/" className="text-sm font-medium text-primary hover:underline">
          Volver al inicio
        </Link>
      </div>
    </Card>
  );
}

function FieldError({ name, errors }: { name: string; errors: Record<string, string[]> }) {
  const msgs = errors[name];
  if (!msgs || msgs.length === 0) return null;
  return <p className="text-xs text-red-700">{msgs[0]}</p>;
}
