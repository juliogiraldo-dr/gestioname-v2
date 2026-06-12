"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { CompanyProvider } from "@/lib/company";
import { Sidebar, type NavItem } from "@/components/Sidebar";
import { Header } from "@/components/Header";
import { Spinner } from "@/components/ui";
import {
  ChartIcon,
  ClockIcon,
  EntityIcon,
  EuroIcon,
  HomeIcon,
  LeaveIcon,
  SettingsIcon,
  UsersIcon,
} from "@/components/icons";

const ADMIN_ROLES = ["admin", "super-admin", "rrhh-coordinator"];

type GatedNavItem = NavItem & { module?: string };

const ALL_NAV: GatedNavItem[] = [
  { href: "/admin", label: "Inicio", icon: HomeIcon },
  { href: "/admin/configuracion", label: "Configuración", icon: SettingsIcon },
  { href: "/admin/empleados", label: "Empleados", icon: UsersIcon, module: "rrhh" },
  { href: "/admin/fichajes", label: "Fichajes", icon: ClockIcon, module: "rrhh" },
  { href: "/admin/ausencias", label: "Ausencias", icon: LeaveIcon, module: "rrhh" },
  { href: "/admin/entidades", label: "Entidades", icon: EntityIcon, module: "socios" },
  { href: "/admin/socios", label: "Socios", icon: UsersIcon, module: "socios" },
  { href: "/admin/tesoreria", label: "Tesorería", icon: EuroIcon, module: "tesoreria" },
  { href: "/admin/informes", label: "Informes", icon: ChartIcon, module: "rrhh" },
  { href: "/admin/suscripcion", label: "Suscripción", icon: EuroIcon },
];

type Module = { key: string; enabled: boolean };

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { profile, loading } = useAuth();
  const router = useRouter();
  const [modules, setModules] = useState<Module[] | null>(null);
  const [navOpen, setNavOpen] = useState(false);

  const isAdmin = profile?.roles.some((r) => ADMIN_ROLES.includes(r)) ?? false;

  useEffect(() => {
    if (loading) return;
    if (!profile) {
      router.replace("/login");
    } else if (!isAdmin) {
      router.replace("/portal");
    }
  }, [loading, profile, isAdmin, router]);

  useEffect(() => {
    if (!isAdmin) return;
    void (async () => {
      try {
        const res = await api<{ data: Module[] }>("/tenant-modules");
        setModules(res.data);
      } catch {
        setModules([]); // ante un fallo, no ocultamos nada crítico
      }
    })();
  }, [isAdmin]);

  if (loading || (isAdmin && modules === null)) {
    return (
      <div className="flex min-h-full items-center justify-center">
        <Spinner />
      </div>
    );
  }

  if (!profile || !isAdmin) return null;

  const enabled = new Set((modules ?? []).filter((m) => m.enabled).map((m) => m.key));
  const nav = ALL_NAV.filter((item) => !item.module || enabled.has(item.module));

  return (
    <CompanyProvider>
      <div className="flex min-h-full">
        <Sidebar items={nav} homeHref="/admin" mobileOpen={navOpen} onClose={() => setNavOpen(false)} />
        <div className="flex min-h-full flex-1 flex-col">
          <Header onMenuClick={() => setNavOpen(true)} />
          <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
        </div>
      </div>
    </CompanyProvider>
  );
}
