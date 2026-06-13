"use client";

import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { useBranding } from "@/lib/branding";
import { useActiveCompany } from "@/lib/company";
import { LogoutIcon } from "./icons";

const ROLE_LABELS: Record<string, string> = {
  "super-admin": "Super admin",
  admin: "Administrador",
  "rrhh-coordinator": "Coordinador RRHH",
  operator: "Operador",
  employee: "Empleado",
  member: "Socio",
};

export function Header({ onMenuClick }: { onMenuClick?: () => void } = {}) {
  const { profile, logout } = useAuth();
  const { app_name } = useBranding();
  const company = useActiveCompany();
  const router = useRouter();

  const role = profile?.roles[0];
  const initials = (profile?.name ?? "?")
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");

  async function onLogout() {
    await logout();
    router.push("/"); // landing pública
  }

  return (
    <header className="flex h-16 items-center justify-between border-b border-line bg-surface px-6">
      <div className="flex items-center gap-4">
        {onMenuClick && (
          <button onClick={onMenuClick} aria-label="Abrir menú" className="text-ink-soft hover:text-ink md:hidden">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
          </button>
        )}
        <div className="text-sm font-semibold text-primary md:hidden">{app_name}</div>
        {company && company.companies.length > 1 && (
          <label className="flex items-center gap-2 text-sm">
            <span className="hidden text-ink-soft sm:inline">Empresa:</span>
            <select
              value={company.activeId}
              onChange={(e) => company.setActiveId(e.target.value)}
              className="rounded-[var(--radius-fluent)] border border-line bg-canvas px-2 py-1.5 text-sm outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/30"
            >
              {company.companies.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </label>
        )}
      </div>
      <div className="ml-auto flex items-center gap-4">
        <div className="text-right">
          <p className="text-sm font-medium text-ink">{profile?.name}</p>
          {role && <p className="text-xs text-ink-soft">{ROLE_LABELS[role] ?? role}</p>}
        </div>
        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-secondary/20 text-sm font-semibold text-primary">
          {initials}
        </div>
        <button
          onClick={onLogout}
          title="Cerrar sesión"
          className="flex h-9 w-9 items-center justify-center rounded-[var(--radius-fluent)] text-ink-soft transition-colors hover:bg-line/60 hover:text-ink"
        >
          <LogoutIcon className="h-5 w-5" />
        </button>
      </div>
    </header>
  );
}
