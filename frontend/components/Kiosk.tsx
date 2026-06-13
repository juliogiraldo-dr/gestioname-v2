"use client";

import { useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { formatTime } from "@/lib/utils";
import { SelectField } from "@/components/ui";

type Config = { entrada: string; salida: string };
type Feedback = { kind: "ok" | "error"; text: string } | null;
type Pending = { milestoneId: string; label: string };
type WorkMode = "presencial" | "teletrabajo";

const CONFIG_KEY = "gm_kiosk_milestones";

function readConfig(): Config | null {
  if (typeof window === "undefined") return null;
  const raw = window.localStorage.getItem(CONFIG_KEY);
  return raw ? (JSON.parse(raw) as Config) : null;
}

/** Pide la ubicación del navegador. Resuelve null si se deniega o no está disponible. */
function getLocation(): Promise<{ lat: number; lng: number } | null> {
  return new Promise((resolve) => {
    if (typeof navigator === "undefined" || !navigator.geolocation) {
      resolve(null);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => resolve(null),
      { timeout: 8000, enableHighAccuracy: true },
    );
  });
}

export function Kiosk() {
  const [config, setConfig] = useState<Config | null>(readConfig);
  const [pin, setPin] = useState("");
  const [name, setName] = useState<string | null>(null);
  const [locationRequired, setLocationRequired] = useState(false);
  const [pending, setPending] = useState<Pending | null>(null);
  const [feedback, setFeedback] = useState<Feedback>(null);
  const [busy, setBusy] = useState(false);

  // Al completar el PIN, identifica al empleado (nombre + si su centro exige ubicación).
  useEffect(() => {
    if (pin.length !== 8) return;
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: { name: string; location_required: boolean } }>("/attendance/identify", {
          method: "POST", auth: false, body: { clock_code: pin },
        });
        if (active) {
          setName(res.data.name);
          setLocationRequired(res.data.location_required);
        }
      } catch {
        if (active) setName(null);
      }
    })();
    return () => { active = false; };
  }, [pin]);

  function press(key: string) {
    setName(null);
    setFeedback(null);
    setPending(null);
    if (key === "C") setPin("");
    else if (key === "←") setPin((p) => p.slice(0, -1));
    else if (pin.length < 8) setPin((p) => p + key);
  }

  function choose(milestoneId: string, label: string) {
    if (pin.length !== 8) {
      setFeedback({ kind: "error", text: "Introduce un código de 8 dígitos" });
      return;
    }
    setFeedback(null);
    setPending({ milestoneId, label });
  }

  async function clock(mode: WorkMode) {
    if (!pending) return;
    setBusy(true);
    setFeedback(null);
    try {
      let location: { lat: number; lng: number } | null = null;
      if (mode === "presencial") {
        location = await getLocation();
        if (location === null && locationRequired) {
          setFeedback({ kind: "error", text: "Este centro requiere ubicación para fichar presencialmente." });
          setBusy(false);
          return;
        }
      }

      const res = await api<{ data: { employee: { name: string }; milestone: { name: string } } }>(
        "/attendance/clock",
        {
          method: "POST", auth: false,
          body: { clock_code: pin, milestone_id: pending.milestoneId, work_mode: mode, lat: location?.lat, lng: location?.lng },
        },
      );
      const modeLabel = mode === "presencial" ? "Oficina" : "Teletrabajo";
      setFeedback({ kind: "ok", text: `${res.data.employee.name} · ${res.data.milestone.name} · ${modeLabel} · ${formatTime(new Date())}` });
      setPin("");
      setName(null);
      setPending(null);
      setTimeout(() => setFeedback(null), 5000);
    } catch (err) {
      setFeedback({ kind: "error", text: err instanceof ApiError ? err.message : "Error al fichar" });
    } finally {
      setBusy(false);
    }
  }

  if (!config) return <KioskSetup onSave={setConfig} />;

  return (
    <main className="flex min-h-full flex-col items-center justify-center bg-primary px-4 py-10 text-white">
      <h1 className="mb-1 text-2xl font-semibold">Reloj de fichaje</h1>
      <p className="mb-6 h-5 text-sm text-white/70">{name ? `Hola, ${name}` : "Introduce tu código y marca"}</p>

      <div className="mb-6 flex gap-2">
        {Array.from({ length: 8 }).map((_, i) => (
          <span key={i} className={`h-3.5 w-3.5 rounded-full ${i < pin.length ? "bg-accent" : "bg-white/25"}`} />
        ))}
      </div>

      {pending === null ? (
        <>
          <div className="grid w-full max-w-xs grid-cols-3 gap-3">
            {["1", "2", "3", "4", "5", "6", "7", "8", "9", "C", "0", "←"].map((key) => (
              <button
                key={key}
                onClick={() => press(key)}
                className="rounded-[var(--radius-fluent)] bg-white/10 py-5 text-2xl font-medium transition-colors hover:bg-white/20"
              >
                {key}
              </button>
            ))}
          </div>

          <div className="mt-8 grid w-full max-w-xs grid-cols-2 gap-3">
            <button disabled={busy} onClick={() => choose(config.entrada, "ENTRADA")}
              className="rounded-[var(--radius-fluent)] bg-accent py-5 text-lg font-semibold text-primary transition-opacity hover:opacity-90 disabled:opacity-50">
              ↳ ENTRADA
            </button>
            <button disabled={busy} onClick={() => choose(config.salida, "SALIDA")}
              className="rounded-[var(--radius-fluent)] bg-secondary py-5 text-lg font-semibold text-primary transition-opacity hover:opacity-90 disabled:opacity-50">
              ↰ SALIDA
            </button>
          </div>
        </>
      ) : (
        <div className="w-full max-w-xs">
          <p className="mb-4 text-center text-sm text-white/80">
            {pending.label} · ¿Desde dónde fichas?
            {locationRequired && <span className="mt-1 block text-xs text-white/60">Presencial requiere compartir ubicación.</span>}
          </p>
          <div className="grid grid-cols-2 gap-3">
            <button disabled={busy} onClick={() => clock("presencial")}
              className="rounded-[var(--radius-fluent)] bg-accent py-6 text-lg font-semibold text-primary transition-opacity hover:opacity-90 disabled:opacity-50">
              🏢 Oficina
            </button>
            <button disabled={busy} onClick={() => clock("teletrabajo")}
              className="rounded-[var(--radius-fluent)] bg-secondary py-6 text-lg font-semibold text-primary transition-opacity hover:opacity-90 disabled:opacity-50">
              🏠 Teletrabajo
            </button>
          </div>
          <button disabled={busy} onClick={() => setPending(null)}
            className="mt-3 w-full rounded-[var(--radius-fluent)] bg-white/10 py-2 text-sm transition-colors hover:bg-white/20 disabled:opacity-50">
            Cancelar
          </button>
        </div>
      )}

      {feedback && (
        <div className={`mt-8 rounded-[var(--radius-fluent)] px-5 py-4 text-center text-base font-semibold ${
          feedback.kind === "ok" ? "bg-accent text-primary" : "bg-red-500/90 text-white"
        }`}>
          {feedback.kind === "ok" ? "✓ " : "⚠ "}{feedback.text}
        </div>
      )}
    </main>
  );
}

type KioskMilestone = { id: string; name: string; type: string };
type KioskCompany = { id: string; name: string; milestones: KioskMilestone[] };

function KioskSetup({ onSave }: { onSave: (c: Config) => void }) {
  const [companies, setCompanies] = useState<KioskCompany[] | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [companyId, setCompanyId] = useState("");
  const [entrada, setEntrada] = useState("");
  const [salida, setSalida] = useState("");

  // Carga las empresas y sus hitos para elegirlos en lugar de pegar UUIDs.
  useEffect(() => {
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: KioskCompany[] }>("/kiosk/options", { auth: false });
        if (!active) return;
        setCompanies(res.data);
        if (res.data.length > 0) setCompanyId(res.data[0].id);
      } catch {
        if (active) setLoadError("No se pudieron cargar las empresas. Inténtalo de nuevo más tarde.");
      }
    })();
    return () => { active = false; };
  }, []);

  const company = companies?.find((c) => c.id === companyId) ?? null;
  const milestones = company?.milestones ?? [];
  // Prioriza los hitos del tipo correspondiente; si no hay, ofrece todos.
  const byType = (type: string) => {
    const matching = milestones.filter((m) => m.type === type);
    return matching.length > 0 ? matching : milestones;
  };
  const toOptions = (list: KioskMilestone[]): ReadonlyArray<readonly [string, string]> =>
    [["", "Selecciona un hito"] as const, ...list.map((m) => [m.id, m.name] as const)];

  function pickCompany(id: string) {
    setCompanyId(id);
    setEntrada("");
    setSalida("");
  }

  return (
    <main className="flex min-h-full items-center justify-center px-4">
      <div className="w-full max-w-md rounded-[var(--radius-fluent)] border border-line bg-surface p-6 shadow-[var(--shadow-fluent)]">
        <h1 className="text-lg font-semibold text-primary">Configurar reloj</h1>
        <p className="mt-1 mb-5 text-sm text-ink-soft">
          Elige la empresa y los hitos de ENTRADA y SALIDA que usará este reloj de fichaje.
        </p>

        {loadError ? (
          <p className="text-sm text-red-600">{loadError}</p>
        ) : companies === null ? (
          <p className="text-sm text-ink-soft">Cargando…</p>
        ) : companies.length === 0 ? (
          <p className="text-sm text-ink-soft">
            No hay empresas con hitos configurados. El administrador debe crearlos en Configuración → Hitos.
          </p>
        ) : (
          <div className="space-y-3">
            <SelectField
              label="Empresa"
              value={companyId}
              onChange={pickCompany}
              options={companies.map((c) => [c.id, c.name] as const)}
            />
            <SelectField
              label="Hito de ENTRADA"
              value={entrada}
              onChange={setEntrada}
              options={toOptions(byType("entrada"))}
            />
            <SelectField
              label="Hito de SALIDA"
              value={salida}
              onChange={setSalida}
              options={toOptions(byType("salida"))}
            />
            <button
              onClick={() => { const c = { entrada, salida }; window.localStorage.setItem(CONFIG_KEY, JSON.stringify(c)); onSave(c); }}
              disabled={!entrada || !salida}
              className="w-full rounded-[var(--radius-fluent)] bg-primary py-2.5 text-sm font-medium text-white disabled:opacity-50">
              Guardar
            </button>
          </div>
        )}
      </div>
    </main>
  );
}
