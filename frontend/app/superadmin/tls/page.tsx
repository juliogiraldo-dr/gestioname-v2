"use client";

import { useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { useToast } from "@/lib/toast";
import { formatDate } from "@/lib/utils";
import { Badge, Button, Card, Field, PageHeader, Skeleton } from "@/components/ui";

type CertInfo = {
  domain: string | null;
  cn: string | null;
  san: string | null;
  valid_until: string | null;
  days_left: number | null;
};

const TEXTAREA =
  "w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 font-mono text-xs outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30";

function expiryTone(days: number | null): "ok" | "warn" | "neutral" {
  if (days === null) return "neutral";
  if (days <= 7) return "warn"; // crítico
  if (days <= 30) return "warn";
  return "ok";
}

export default function TlsCertificatePage() {
  const toast = useToast();
  const [cert, setCert] = useState<CertInfo | null>(null);
  const [loaded, setLoaded] = useState(false);
  const [certificate, setCertificate] = useState("");
  const [privateKey, setPrivateKey] = useState("");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: CertInfo | null }>("/superadmin/tls-certificate");
        if (active) setCert(res.data);
      } catch {
        if (active) setCert(null);
      } finally {
        if (active) setLoaded(true);
      }
    })();
    return () => {
      active = false;
    };
  }, []);

  async function save() {
    if (!certificate.trim() || !privateKey.trim()) {
      toast.warning("Pega el certificado y la clave privada.");
      return;
    }
    setSaving(true);
    try {
      const res = await api<{ data: CertInfo }>("/superadmin/tls-certificate", {
        method: "PUT",
        body: { certificate, private_key: privateKey },
      });
      setCert(res.data);
      setCertificate("");
      setPrivateKey("");
      toast.success(`Certificado guardado para ${res.data.cn ?? "el dominio"}.`);
    } catch (e) {
      toast.error(e instanceof ApiError ? e.message : "No se pudo guardar el certificado.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="max-w-3xl">
      <PageHeader title="TLS / Certificado" subtitle="Certificado wildcard de la plataforma (*.app.gestioname.es)" />

      {!loaded ? (
        <Skeleton rows={3} />
      ) : (
        <Card className="mb-6 p-5">
          <h2 className="mb-3 text-sm font-semibold text-primary">Certificado actual</h2>
          {cert === null ? (
            <p className="text-sm text-ink-soft">No hay ningún certificado subido todavía.</p>
          ) : (
            <div className="space-y-2 text-sm">
              <Row label="Dominio (CN)" value={cert.cn ?? "—"} />
              {cert.san && <Row label="SAN" value={cert.san} />}
              <Row label="Válido hasta" value={formatDate(cert.valid_until)} />
              <div className="flex items-center justify-between border-b border-line py-2 last:border-0">
                <span className="text-ink-soft">Días restantes</span>
                <Badge tone={expiryTone(cert.days_left)}>
                  {cert.days_left === null
                    ? "—"
                    : cert.days_left < 0
                      ? `caducado hace ${Math.abs(cert.days_left)} días`
                      : `${cert.days_left} días`}
                </Badge>
              </div>
              {cert.days_left !== null && cert.days_left <= 30 && (
                <p className="mt-2 rounded-[var(--radius-fluent)] bg-amber-50 px-3 py-2 text-xs text-amber-800">
                  ⚠ El certificado {cert.days_left < 0 ? "ha caducado" : `caduca en ${cert.days_left} días`}. Renuévalo en Plesk y súbelo aquí.
                </p>
              )}
            </div>
          )}
        </Card>
      )}

      <Card className="p-5">
        <h2 className="mb-1 text-sm font-semibold text-primary">Subir nuevo certificado</h2>
        <p className="mb-4 text-xs text-ink-soft">
          Genera el wildcard en Plesk (Let&apos;s Encrypt DNS-01), exporta el certificado (fullchain) y la clave
          privada, y pégalos aquí. Tras guardar, reinicia el frontend para que Traefik lo recoja.
        </p>
        <div className="space-y-4">
          <Field label="Certificado (-----BEGIN CERTIFICATE-----)">
            <textarea
              value={certificate}
              onChange={(e) => setCertificate(e.target.value)}
              rows={8}
              spellCheck={false}
              placeholder="-----BEGIN CERTIFICATE-----&#10;...&#10;-----END CERTIFICATE-----"
              className={TEXTAREA}
            />
          </Field>
          <Field label="Clave privada (-----BEGIN PRIVATE KEY-----)">
            <textarea
              value={privateKey}
              onChange={(e) => setPrivateKey(e.target.value)}
              rows={6}
              spellCheck={false}
              placeholder="-----BEGIN PRIVATE KEY-----&#10;...&#10;-----END PRIVATE KEY-----"
              className={TEXTAREA}
            />
          </Field>
          <div className="flex justify-end">
            <Button onClick={save} disabled={saving}>{saving ? "Verificando…" : "Verificar y guardar"}</Button>
          </div>
        </div>
      </Card>
    </div>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-line py-2 last:border-0">
      <span className="text-ink-soft">{label}</span>
      <span className="break-all text-right font-medium text-ink">{value}</span>
    </div>
  );
}
