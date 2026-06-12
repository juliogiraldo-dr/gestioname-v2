"use client";

import Link from "next/link";
import { useAuth } from "@/lib/auth";
import { Card, PageHeader } from "@/components/ui";
import { PlanBanners } from "@/components/PlanBanners";
import { ChartIcon, ClockIcon, EntityIcon, EuroIcon, LeaveIcon, SettingsIcon, UsersIcon } from "@/components/icons";

const LINKS = [
  { href: "/admin/configuracion", icon: SettingsIcon, title: "Configuración", desc: "Módulos, empresas, convenios y calendarios" },
  { href: "/admin/empleados", icon: UsersIcon, title: "Empleados", desc: "Fichas, altas e incidencias" },
  { href: "/admin/fichajes", icon: ClockIcon, title: "Fichajes", desc: "Vista diaria y correcciones (ET 34.9)" },
  { href: "/admin/ausencias", icon: LeaveIcon, title: "Ausencias", desc: "Aprobaciones y vacaciones" },
  { href: "/admin/entidades", icon: EntityIcon, title: "Entidades", desc: "Asociaciones, tipos de socio y cuotas" },
  { href: "/admin/socios", icon: UsersIcon, title: "Socios", desc: "Fichas, pagos y estados" },
  { href: "/admin/tesoreria", icon: EuroIcon, title: "Tesorería", desc: "Ingresos, gastos y saldo" },
  { href: "/admin/informes", icon: ChartIcon, title: "Informes", desc: "Registro horario, diario y ausencias" },
];

export default function AdminHomePage() {
  const { profile } = useAuth();

  return (
    <div>
      <PageHeader
        title={`Hola, ${profile?.name?.split(" ")[0] ?? ""}`}
        subtitle="Panel de administración de Gestioname"
      />
      <PlanBanners />
      <p className="mb-6 text-sm text-ink-soft">
        Accede a los módulos activos de tu organización. Puedes activar o desactivar módulos en Configuración.
      </p>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {LINKS.map(({ href, icon: Icon, title, desc }) => (
          <Link key={href} href={href}>
            <Card className="p-5 transition-shadow hover:shadow-[var(--shadow-fluent-lg)]">
              <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-[var(--radius-fluent)] bg-secondary/15 text-primary">
                <Icon className="h-5 w-5" />
              </div>
              <p className="font-medium text-ink">{title}</p>
              <p className="mt-1 text-sm text-ink-soft">{desc}</p>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
