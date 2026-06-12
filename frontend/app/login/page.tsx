"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { useBranding } from "@/lib/branding";
import { ApiError } from "@/lib/api";
import { Button, Card } from "@/components/ui";

export default function LoginPage() {
  const { login } = useAuth();
  const { app_name, logo_path } = useBranding();
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setBusy(true);
    try {
      await login(email, password);
      router.push("/"); // la home raíz enruta por rol (admin → /admin, resto → /portal)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "No se pudo iniciar sesión");
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="flex min-h-full items-center justify-center px-4">
      <div className="w-full max-w-sm">
        <div className="mb-8 text-center">
          {logo_path ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={logo_path} alt={app_name} className="mx-auto mb-3 h-12 w-12 rounded-xl object-contain" />
          ) : (
            <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-lg font-semibold text-white">
              {app_name.charAt(0).toUpperCase()}
            </div>
          )}
          <h1 className="text-xl font-semibold text-primary">{app_name}</h1>
          <p className="mt-1 text-sm text-ink-soft">Accede a tu portal</p>
        </div>

        <Card className="p-6">
          <form onSubmit={onSubmit} className="space-y-4">
            <Field label="Email">
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                placeholder="admin@demo.gestioname.app"
              />
            </Field>
            <Field label="Contraseña">
              <input
                type="password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
                placeholder="••••••••"
              />
            </Field>

            {error && (
              <p className="rounded-[var(--radius-fluent)] bg-red-50 px-3 py-2 text-sm text-red-700">{error}</p>
            )}

            <Button type="submit" disabled={busy} className="w-full">
              {busy ? "Accediendo…" : "Entrar"}
            </Button>
          </form>
        </Card>
      </div>
    </main>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block">
      <span className="mb-1.5 block text-sm font-medium text-ink">{label}</span>
      {children}
    </label>
  );
}
