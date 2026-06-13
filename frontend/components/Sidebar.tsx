"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import type { ComponentType } from "react";
import { useBranding } from "@/lib/branding";
import { BuildingIcon, CalendarIcon, ClockIcon, DocIcon, EuroIcon, HomeIcon, LeaveIcon, NewsIcon, UsersIcon } from "./icons";

export type NavItem = {
  href: string;
  label: string;
  icon: ComponentType<{ className?: string }>;
};

const PORTAL_NAV: NavItem[] = [
  { href: "/portal", label: "Inicio", icon: HomeIcon },
  { href: "/portal/datos", label: "Mis datos", icon: UsersIcon },
  { href: "/portal/fichajes", label: "Mis fichajes", icon: ClockIcon },
  { href: "/portal/horario", label: "Mi horario", icon: CalendarIcon },
  { href: "/portal/ausencias", label: "Ausencias", icon: LeaveIcon },
  { href: "/portal/laboral", label: "Datos laborales", icon: BuildingIcon },
  { href: "/portal/nominas", label: "Mis nóminas", icon: DocIcon },
  { href: "/portal/documentos", label: "Documentos", icon: DocIcon },
  { href: "/portal/noticias", label: "Noticias", icon: NewsIcon },
];

// Navegación mínima del socio (rol "member"): solo su cuota, en modo lectura.
export const MEMBER_NAV: NavItem[] = [
  { href: "/portal/socio", label: "Mi cuota", icon: EuroIcon },
];

export function Sidebar({
  items = PORTAL_NAV,
  homeHref = "/portal",
  mobileOpen = false,
  onClose,
}: {
  items?: NavItem[];
  homeHref?: string;
  mobileOpen?: boolean;
  onClose?: () => void;
} = {}) {
  const pathname = usePathname();
  const { app_name, logo_path } = useBranding();

  const brand = (
    <div className="mb-6 flex items-center gap-2.5 px-2">
      {logo_path ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={logo_path} alt={app_name} className="h-9 w-9 rounded-lg object-contain" />
      ) : (
        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-white">
          {app_name.charAt(0).toUpperCase()}
        </div>
      )}
      <span className="text-base font-semibold text-primary">{app_name}</span>
    </div>
  );

  const nav = (
    <nav className="flex flex-col gap-1">
      {items.map(({ href, label, icon: Icon }) => {
        const active = href === homeHref ? pathname === href : pathname.startsWith(href);
        return (
          <Link
            key={href}
            href={href}
            onClick={onClose}
            className={`flex items-center gap-3 rounded-[var(--radius-fluent)] px-3 py-2 text-sm transition-colors ${
              active ? "bg-secondary/15 font-medium text-primary" : "text-ink-soft hover:bg-line/60 hover:text-ink"
            }`}
          >
            <Icon className="h-5 w-5" />
            {label}
          </Link>
        );
      })}
    </nav>
  );

  return (
    <>
      {/* Escritorio */}
      <aside className="hidden w-64 shrink-0 flex-col border-r border-line bg-surface px-3 py-5 md:flex">
        {brand}
        {nav}
      </aside>

      {/* Móvil: drawer deslizante */}
      {mobileOpen && (
        <div className="fixed inset-0 z-[90] md:hidden">
          <div className="absolute inset-0 bg-primary/30 backdrop-blur-sm" onClick={onClose} />
          <aside className="absolute left-0 top-0 flex h-full w-64 flex-col border-r border-line bg-surface px-3 py-5 shadow-[var(--shadow-fluent-lg)]">
            {brand}
            {nav}
          </aside>
        </div>
      )}
    </>
  );
}
