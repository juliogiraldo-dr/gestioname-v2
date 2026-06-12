"use client";

import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { api, setToken } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { Button, Card, Spinner } from "@/components/ui";

export default function MagicLinkPage() {
  return (
    <Suspense fallback={<div className="flex min-h-full items-center justify-center"><Spinner /></div>}>
      <MagicLinkVerifier />
    </Suspense>
  );
}

/** Verifica un magic link (?token=&tenant=) y deja la sesión iniciada. */
function MagicLinkVerifier() {
  const params = useSearchParams();
  const router = useRouter();
  const { refresh } = useAuth();
  const [failed, setFailed] = useState(false);
  const token = params.get("token");
  const error = !token ? "Enlace no válido." : failed ? "El enlace no es válido o ha caducado." : null;

  useEffect(() => {
    if (!token) return;
    const tenant = params.get("tenant") ?? undefined;
    void (async () => {
      try {
        const res = await api<{ data: { token: string } }>("/auth/magic-link/verify", {
          method: "POST", auth: false, tenant, body: { token },
        });
        setToken(res.data.token);
        await refresh();
        router.replace("/");
      } catch {
        setFailed(true);
      }
    })();
  }, [params, token, router, refresh]);

  return (
    <main className="flex min-h-full items-center justify-center px-4">
      {error ? (
        <Card className="max-w-sm p-8 text-center">
          <h1 className="text-lg font-semibold text-primary">No se pudo acceder</h1>
          <p className="mt-2 text-sm text-ink-soft">{error}</p>
          <div className="mt-4"><Button onClick={() => router.replace("/login")}>Ir al login</Button></div>
        </Card>
      ) : (
        <div className="text-center">
          <Spinner />
          <p className="mt-2 text-sm text-ink-soft">Verificando tu enlace…</p>
        </div>
      )}
    </main>
  );
}
