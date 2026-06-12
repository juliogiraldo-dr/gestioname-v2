import { type ButtonHTMLAttributes, type ReactNode } from "react";

const INPUT_CLASS =
  "w-full rounded-[var(--radius-fluent)] border border-line bg-canvas px-3 py-2 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30 disabled:opacity-60";

/** Etiqueta + control de formulario. */
export function Field({ label, children, className = "" }: { label: string; children: ReactNode; className?: string }) {
  return (
    <label className={`block ${className}`}>
      <span className="mb-1.5 block text-sm font-medium text-ink">{label}</span>
      {children}
    </label>
  );
}

export function TextField({
  label,
  value,
  onChange,
  type = "text",
  placeholder,
  className = "",
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  type?: string;
  placeholder?: string;
  className?: string;
}) {
  return (
    <Field label={label} className={className}>
      <input type={type} value={value} placeholder={placeholder} onChange={(e) => onChange(e.target.value)} className={INPUT_CLASS} />
    </Field>
  );
}

export function SelectField({
  label,
  value,
  onChange,
  options,
  className = "",
}: {
  label: string;
  value: string;
  onChange: (v: string) => void;
  options: ReadonlyArray<readonly [string, string]>;
  className?: string;
}) {
  return (
    <Field label={label} className={className}>
      <select value={value} onChange={(e) => onChange(e.target.value)} className={INPUT_CLASS}>
        {options.map(([v, l]) => (
          <option key={v} value={v}>{l}</option>
        ))}
      </select>
    </Field>
  );
}

/** Interruptor de activación estilo Fluent. */
export function Toggle({ on, onClick, label }: { on: boolean; onClick: () => void; label?: string }) {
  return (
    <button type="button" onClick={onClick} className="inline-flex items-center gap-2">
      <span className={`relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors ${on ? "bg-accent" : "bg-line"}`}>
        <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${on ? "translate-x-4" : "translate-x-0.5"}`} />
      </span>
      {label && <span className="text-sm text-ink">{label}</span>}
    </button>
  );
}

/** Estado vacío centrado con CTA opcional. */
export function EmptyState({ title, message, action }: { title: string; message?: string; action?: ReactNode }) {
  return (
    <Card className="p-10 text-center">
      <h2 className="text-lg font-semibold text-primary">{title}</h2>
      {message && <p className="mx-auto mt-2 max-w-md text-sm text-ink-soft">{message}</p>}
      {action && <div className="mt-5">{action}</div>}
    </Card>
  );
}

/** Diálogo modal centrado para formularios. */
export function Modal({ title, onClose, children }: { title: string; onClose: () => void; children: ReactNode }) {
  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-primary/30 p-4 backdrop-blur-sm sm:p-8">
      <div className="w-full max-w-2xl rounded-[var(--radius-fluent)] border border-line bg-surface shadow-[var(--shadow-fluent-lg)]">
        <div className="flex items-center justify-between border-b border-line px-5 py-3">
          <h2 className="text-base font-semibold text-primary">{title}</h2>
          <button onClick={onClose} className="text-ink-soft hover:text-ink" aria-label="Cerrar">✕</button>
        </div>
        <div className="p-5">{children}</div>
      </div>
    </div>
  );
}

/** Tarjeta base Fluent: superficie blanca, borde sutil, sombra ligera. */
export function Card({ children, className = "" }: { children: ReactNode; className?: string }) {
  return (
    <div
      className={`rounded-[var(--radius-fluent)] border border-line bg-surface shadow-[var(--shadow-fluent)] ${className}`}
    >
      {children}
    </div>
  );
}

export function PageHeader({ title, subtitle, action }: { title: string; subtitle?: string; action?: ReactNode }) {
  return (
    <div className="mb-6 flex items-end justify-between gap-4">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight text-primary">{title}</h1>
        {subtitle && <p className="mt-1 text-sm text-ink-soft">{subtitle}</p>}
      </div>
      {action}
    </div>
  );
}

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "secondary" | "ghost";
};

export function Button({ variant = "primary", className = "", ...props }: ButtonProps) {
  const styles = {
    primary: "bg-primary text-white hover:bg-primary-600",
    secondary: "bg-secondary/15 text-primary hover:bg-secondary/25",
    ghost: "text-ink-soft hover:bg-line/60",
  }[variant];

  return (
    <button
      className={`inline-flex items-center justify-center gap-2 rounded-[var(--radius-fluent)] px-4 py-2 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 ${styles} ${className}`}
      {...props}
    />
  );
}

export function StatCard({ label, value, hint }: { label: string; value: ReactNode; hint?: string }) {
  return (
    <Card className="p-5">
      <p className="text-sm text-ink-soft">{label}</p>
      <p className="mt-2 text-3xl font-semibold text-primary">{value}</p>
      {hint && <p className="mt-1 text-xs text-ink-soft">{hint}</p>}
    </Card>
  );
}

export function Badge({ children, tone = "neutral" }: { children: ReactNode; tone?: "neutral" | "ok" | "warn" | "info" }) {
  const tones = {
    neutral: "bg-line text-ink-soft",
    ok: "bg-accent/20 text-[#0d6b50]",
    warn: "bg-amber-100 text-amber-700",
    info: "bg-secondary/20 text-primary",
  }[tone];

  return <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${tones}`}>{children}</span>;
}

export function Spinner() {
  return (
    <div className="flex items-center justify-center p-8">
      <div className="h-6 w-6 animate-spin rounded-full border-2 border-secondary border-t-transparent" />
    </div>
  );
}

/** Avatar con iniciales (estilo Fluent). */
export function Avatar({ name, className = "" }: { name: string; className?: string }) {
  const initials = (name || "?")
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
  return (
    <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary/20 text-sm font-semibold text-primary ${className}`}>
      {initials || "?"}
    </span>
  );
}

/** Migas de pan para páginas de detalle. */
export function Breadcrumb({ items }: { items: ReadonlyArray<{ label: string; href?: string }> }) {
  return (
    <nav className="mb-2 flex flex-wrap items-center gap-1.5 text-xs text-ink-soft">
      {items.map((it, i) => (
        <span key={i} className="flex items-center gap-1.5">
          {i > 0 && <span className="text-line">/</span>}
          {it.href ? <a href={it.href} className="hover:text-primary hover:underline">{it.label}</a> : <span className="text-ink">{it.label}</span>}
        </span>
      ))}
    </nav>
  );
}

/** Separador con título para agrupar campos en formularios largos. */
export function FormSection({ title, children }: { title: string; children: ReactNode }) {
  return (
    <fieldset className="border-t border-line pt-4">
      <legend className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-soft">{title}</legend>
      {children}
    </fieldset>
  );
}

/** Placeholder de carga (skeleton) para listados. */
export function Skeleton({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-2 p-5">
      {Array.from({ length: rows }).map((_, i) => (
        <div key={i} className="h-10 animate-pulse rounded-[var(--radius-fluent)] bg-line/60" />
      ))}
    </div>
  );
}
