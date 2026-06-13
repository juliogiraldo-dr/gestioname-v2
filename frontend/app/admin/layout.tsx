"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { CompanyProvider } from "@/lib/company";
import { Sidebar, type NavItem } from "@/components/Sidebar";
import { Header } from "@/components/Header";
import { Spinner } from "@/components/ui";
import {
  ChartIcon,
  ClockIcon,
  DocIcon,
  EntityIcon,
  EuroIcon,
  HomeIcon,
  LeaveIcon,
  NewsIcon,
  SettingsIcon,
  UsersIcon,
} from "@/components/icons";

// Roles que gestionan toda la empresa. La gestoría externa entra con una navegación reducida.
const FULL_ADMIN_ROLES = ["admin", "super-admin", "rrhh-coordinator"];

type GatedNavItem = NavItem & { module?: string };

const ALL_NAV: GatedNavItem[] = [
  { href: "/admin", label: "Inicio", icon: HomeIcon },
  { href: "/admin/configuracion", label: "Configuración", icon: SettingsIcon },
  { href: "/admin/empleados", label: "Empleados", icon: UsersIcon, module: "rrhh" },
  { href: "/admin/organigrama", label: "Organigrama", icon: UsersIcon, module: "rrhh" },
  { href: "/admin/fichajes", label: "Fichajes", icon: ClockIcon, module: "rrhh" },
  { href: "/admin/ausencias", label: "Ausencias", icon: LeaveIcon, module: "rrhh" },
  { href: "/admin/gestoria", label: "Gestoría", icon: DocIcon, module: "rrhh" },
  { href: "/admin/entidades", label: "Entidades", icon: EntityIcon, module: "socios" },
  { href: "/admin/socios", label: "Socios", icon: UsersIcon, module: "socios" },
  { href: "/admin/tesoreria", label: "Tesorería", icon: EuroIcon, module: "tesoreria" },
  { href: "/admin/contabilidad", label: "Contabilidad", icon: ChartIcon, module: "contabilidad" },
  { href: "/admin/informes", label: "Informes", icon: ChartIcon, module: "rrhh" },
  { href: "/admin/comunicaciones", label: "Comunicaciones", icon: NewsIcon },
  { href: "/admin/suscripcion", label: "Suscripción", icon: EuroIcon },
];

// Navegación de la gestoría externa: solo nóminas/descargas e informes.
const GESTORIA_NAV: NavItem[] = [
  { href: "/admin/gestoria", label: "Gestoría", icon: DocIcon },
  { href: "/admin/informes", label: "Informes", icon: ChartIcon },
];

type Module = { key: string; enabled: boolean };

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const { profile, loading } = useAuth();
  const router = useRouter();
  const [navOpen, setNavOpen] = useState(false);

  const isFullAdmin = profile?.roles.some((r) => FULL_ADMIN_ROLES.includes(r)) ?? false;
  const isGestoria = profile?.roles.includes("gestoria") ?? false;
  const canEnter = isFullAdmin || isGestoria;

  useEffect(() => {
    if (loading) return;
    if (!profile) {
      router.replace("/login");
    } else if (!canEnter) {
      router.replace("/portal");
    }
  }, [loading, profile, canEnter, router]);

  // Módulos activos del tenant (gating del sidebar). Cacheado con React Query: al
  // activar/desactivar un módulo se invalida ["tenant-modules"] y el sidebar se recarga.
  const { data: modules } = useQuery({
    queryKey: ["tenant-modules"],
    enabled: isFullAdmin,
    queryFn: async () => {
      try {
        return (await api<{ data: Module[] }>("/tenant-modules")).data;
      } catch {
        return [] as Module[];
      }
    },
  });

  if (loading || (isFullAdmin && modules === undefined)) {
    return (
      <div className="flex min-h-full items-center justify-center">
        <Spinner />
      </div>
    );
  }

  if (!profile || !canEnter) return null;

  const enabled = new Set((modules ?? []).filter((m) => m.enabled).map((m) => m.key));
  const nav = isFullAdmin
    ? ALL_NAV.filter((item) => !item.module || enabled.has(item.module))
    : GESTORIA_NAV;

  return (
    <CompanyProvider>
      <div className="flex min-h-full">
        <Sidebar items={nav} homeHref={isFullAdmin ? "/admin" : "/admin/gestoria"} mobileOpen={navOpen} onClose={() => setNavOpen(false)} />
        <div className="flex min-h-full flex-1 flex-col">
          <Header onMenuClick={() => setNavOpen(true)} />
          <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
        </div>
      </div>
    </CompanyProvider>
  );
}
