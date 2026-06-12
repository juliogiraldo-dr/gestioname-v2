"use client";

import { useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";

type Config = { entrada: string; salida: string };
type Feedback = { kind: "ok" | "error"; text: string } | null;

const CONFIG_KEY = "gm_kiosk_milestones";

function readConfig(): Config | null {
  if (typeof window === "undefined") return null;
  const raw = window.localStorage.getItem(CONFIG_KEY);
  return raw ? (JSON.parse(raw) as Config) : null;
}

export function Kiosk() {
  const [config, setConfig] = useState<Config | null>(readConfig);
  const [pin, setPin] = useState("");
  const [name, setName] = useState<string | null>(null);
  const [feedback, setFeedback] = useState<Feedback>(null);
  const [busy, setBusy] = useState(false);

  // Al completar el PIN, identifica al empleado (muestra su nombre antes de fichar).
  useEffect(() => {
    if (pin.length !== 8) return;
    let active = true;
    void (async () => {
      try {
        const res = await api<{ data: { name: string } }>("/attendance/identify", {
          method: "POST", auth: false, body: { clock_code: pin },
        });
        if (active) setName(res.data.name);
      } catch {
        if (active) setName(null);
      }
    })();
    return () => { active = false; };
  }, [pin]);

  function press(key: string) {
    setName(null);
    setFeedback(null);
    if (key === "C") setPin("");
    else if (key === "←") setPin((p) => p.slice(0, -1));
    else if (pin.length < 8) setPin((p) => p + key);
  }

  async function clock(milestoneId: string) {
    if (pin.length !== 8) {
      setFeedback({ kind: "error", text: "Introduce un código de 8 dígitos" });
      return;
    }
    setBusy(true);
    setFeedback(null);
    try {
      const res = await api<{ data: { employee: { name: string }; milestone: { name: string } } }>(
        "/attendance/clock",
        { method: "POST", auth: false, body: { clock_code: pin, milestone_id: milestoneId } },
      );
      const hour = new Date().toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });
      setFeedback({ kind: "ok", text: `${res.data.employee.name} · ${res.data.milestone.name} · ${hour}` });
      setPin("");
      setName(null);
      // Auto-reset del mensaje a los 5 s.
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
        <button disabled={busy} onClick={() => clock(config.entrada)}
          className="rounded-[var(--radius-fluent)] bg-accent py-5 text-lg font-semibold text-primary transition-opacity hover:opacity-90 disabled:opacity-50">
          ↳ ENTRADA
        </button>
        <button disabled={busy} onClick={() => clock(config.salida)}
          className="rounded-[var(--radius-fluent)] bg-secondary py-5 text-lg font-semibold text-primary transition-opacity hover:opacity-90 disabled:opacity-50">
          ↰ SALIDA
        </button>
      </div>

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

function KioskSetup({ onSave }: { onSave: (c: Config) => void }) {
  const [entrada, setEntrada] = useState("");
  const [salida, setSalida] = useState("");

  return (
    <main className="flex min-h-full items-center justify-center px-4">
      <div className="w-full max-w-md rounded-[var(--radius-fluent)] border border-line bg-surface p-6 shadow-[var(--shadow-fluent)]">
        <h1 className="text-lg font-semibold text-primary">Configurar reloj</h1>
        <p className="mt-1 mb-5 text-sm text-ink-soft">
          Pega los identificadores de los hitos ENTRADA y SALIDA de esta empresa (los obtiene el administrador en Configuración → Hitos).
        </p>
        <div className="space-y-3">
          <input value={entrada} onChange={(e) => setEntrada(e.target.value)} placeholder="ID hito ENTRADA"
            className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary" />
          <input value={salida} onChange={(e) => setSalida(e.target.value)} placeholder="ID hito SALIDA"
            className="w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary" />
          <button
            onClick={() => { const c = { entrada, salida }; window.localStorage.setItem(CONFIG_KEY, JSON.stringify(c)); onSave(c); }}
            disabled={!entrada || !salida}
            className="w-full rounded-[var(--radius-fluent)] bg-primary py-2.5 text-sm font-medium text-white disabled:opacity-50">
            Guardar
          </button>
        </div>
      </div>
    </main>
  );
}
