"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Sidebar, type NavItem } from "@/components/Sidebar";
import { Header } from "@/components/Header";
import { Spinner } from "@/components/ui";
import { BuildingIcon, ChartIcon, EuroIcon, HomeIcon, SettingsIcon } from "@/components/icons";

const NAV: NavItem[] = [
  { href: "/superadmin", label: "Inicio", icon: HomeIcon },
  { href: "/superadmin/tenants", label: "Tenants", icon: BuildingIcon },
  { href: "/superadmin/planes", label: "Planes", icon: EuroIcon },
  { href: "/superadmin/auditoria", label: "Auditoría", icon: ChartIcon },
  { href: "/superadmin/tls", label: "TLS / Certificado", icon: SettingsIcon },
];

export default function SuperAdminLayout({ children }: { children: React.ReactNode }) {
  const { profile, loading } = useAuth();
  const router = useRouter();
  const [navOpen, setNavOpen] = useState(false);
  const isSuper = profile?.roles.includes("super-admin") ?? false;

  useEffect(() => {
    if (loading) return;
    if (!profile) router.replace("/login");
    else if (!isSuper) router.replace("/");
  }, [loading, profile, isSuper, router]);

  if (loading) {
    return <div className="flex min-h-full items-center justify-center"><Spinner /></div>;
  }
  if (!profile || !isSuper) return null;

  return (
    <div className="flex min-h-full">
      <Sidebar items={NAV} homeHref="/superadmin" mobileOpen={navOpen} onClose={() => setNavOpen(false)} />
      <div className="flex min-h-full flex-1 flex-col">
        <Header onMenuClick={() => setNavOpen(true)} />
        <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
      </div>
    </div>
  );
}
