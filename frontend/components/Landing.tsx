"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { api, ApiError } from "@/lib/api";
import { useBranding } from "@/lib/branding";
import { useDebounce } from "@/lib/hooks";
import { Button, Card, Field, SelectField, TextField } from "@/components/ui";

const TYPES = [
  ["empresa", "Empresa con empleados"],
  ["entidad", "Asociación o entidad"],
  ["ambas", "Ambas cosas"],
] as const;

export function Landing() {
  const { app_name } = useBranding();
  const [done, setDone] = useState<string | null>(null);

  return (
    <main className="min-h-full">
      <header className="flex items-center justify-between px-6 py-5 sm:px-10">
        <span className="text-lg font-semibold text-primary">{app_name}</span>
        <Link href="/login" className="text-sm font-medium text-primary hover:underline">Iniciar sesión</Link>
      </header>

      <section className="mx-auto grid max-w-6xl gap-10 px-6 py-10 sm:px-10 lg:grid-cols-2 lg:items-center lg:py-20">
        <div>
          <h1 className="text-4xl font-semibold leading-tight text-primary sm:text-5xl">
            Gestión de RRHH, fichajes y asociaciones, en un solo sitio.
          </h1>
          <p className="mt-5 max-w-md text-lg text-ink-soft">
            Control de jornada conforme al ET 34.9, gestión de empleados, socios y tesorería.
            Empieza gratis en menos de un minuto.
          </p>
          <ul className="mt-6 space-y-2 text-sm text-ink-soft">
            <li>✓ Plan gratuito sin tarjeta · 30 días con todo</li>
            <li>✓ Tu subdominio propio en gestioname.app</li>
            <li>✓ Empresa, asociación o ambas</li>
          </ul>
        </div>

        <div>
          {done ? <Confirmation url={done} /> : <RegisterForm onDone={setDone} />}
        </div>
      </section>
    </main>
  );
}

function Confirmation({ url }: { url: string }) {
  return (
    <Card className="p-8 text-center">
      <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-accent/20 text-2xl">✉️</div>
      <h2 className="text-xl font-semibold text-primary">Revisa tu email para acceder</h2>
      <p className="mx-auto mt-2 max-w-sm text-sm text-ink-soft">
        Hemos creado tu cuenta en <span className="font-medium text-ink">{url}</span> y te hemos enviado un enlace de acceso.
      </p>
    </Card>
  );
}

function RegisterForm({ onDone }: { onDone: (url: string) => void }) {
  const [form, setForm] = useState({ name: "", type: "ambas", subdomain: "", admin_email: "" });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [check, setCheck] = useState<{ valid: boolean; available: boolean } | null>(null);
  const debouncedSub = useDebounce(form.subdomain, 300);

  const set = (k: keyof typeof form, v: string) => setForm((p) => ({ ...p, [k]: v }));

  useEffect(() => {
    if (!debouncedSub) return;
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
    return () => { active = false; };
  }, [debouncedSub]);

  async function submit() {
    setError(null);
    setBusy(true);
    try {
      const res = await api<{ data: { url: string } }>("/register", {
        method: "POST",
        auth: false,
        body: { name: form.name, type: form.type, subdomain: form.subdomain.toLowerCase(), admin_email: form.admin_email },
      });
      onDone(res.data.url);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "No se pudo crear la cuenta");
    } finally {
      setBusy(false);
    }
  }

  const subHint = !form.subdomain
    ? null
    : check === null
      ? "Comprobando…"
      : !check.valid
        ? "Subdominio no válido"
        : check.available
          ? "✓ Disponible"
          : "No disponible";

  return (
    <Card className="p-6">
      <h2 className="mb-1 text-xl font-semibold text-primary">Empieza gratis</h2>
      <p className="mb-5 text-sm text-ink-soft">Crea tu espacio en 1 minuto.</p>
      <div className="space-y-4">
        <TextField label="Nombre de la organización" value={form.name} onChange={(v) => set("name", v)} />
        <SelectField label="¿Qué gestionas?" value={form.type} onChange={(v) => set("type", v)} options={TYPES} />
        <Field label="Subdominio deseado">
          <div className="flex items-center gap-2">
            <input
              value={form.subdomain}
              onChange={(e) => set("subdomain", e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ""))}
              placeholder="miempresa"
              className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
            />
            <span className="whitespace-nowrap text-sm text-ink-soft">.gestioname.app</span>
          </div>
          {subHint && (
            <span className={`mt-1 block text-xs ${check?.available ? "text-[#0d6b50]" : "text-ink-soft"}`}>{subHint}</span>
          )}
        </Field>
        <TextField label="Email del administrador" type="email" value={form.admin_email} onChange={(v) => set("admin_email", v)} />
        {error && <p className="rounded-[var(--radius-fluent)] bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>}
        <Button
          onClick={submit}
          className="w-full"
          disabled={busy || !form.name || !form.admin_email || !check?.available}
        >
          {busy ? "Creando…" : "Empieza gratis"}
        </Button>
      </div>
    </Card>
  );
}
