"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Sidebar, MEMBER_NAV } from "@/components/Sidebar";
import { Header } from "@/components/Header";
import { Spinner } from "@/components/ui";

// Roles con perfil de empleado/gestión: ven la navegación completa del portal.
const STAFF_ROLES = ["employee", "operator", "admin", "super-admin", "rrhh-coordinator", "gestoria"];

export default function PortalLayout({ children }: { children: React.ReactNode }) {
  const { profile, loading } = useAuth();
  const router = useRouter();
  const [navOpen, setNavOpen] = useState(false);

  useEffect(() => {
    if (!loading && !profile) router.replace("/login");
  }, [loading, profile, router]);

  if (loading) {
    return (
      <div className="flex min-h-full items-center justify-center">
        <Spinner />
      </div>
    );
  }

  if (!profile) return null;

  // Socio puro (rol "member" y ningún rol de empleado/gestión): navegación reducida.
  const isMemberOnly =
    profile.roles.includes("member") && !profile.roles.some((r) => STAFF_ROLES.includes(r));

  return (
    <div className="flex min-h-full">
      <Sidebar
        items={isMemberOnly ? MEMBER_NAV : undefined}
        homeHref={isMemberOnly ? "/portal/socio" : "/portal"}
        mobileOpen={navOpen}
        onClose={() => setNavOpen(false)}
      />
      <div className="flex min-h-full flex-1 flex-col">
        <Header onMenuClick={() => setNavOpen(true)} />
        <main className="flex-1 p-4 sm:p-6 lg:p-8">{children}</main>
      </div>
    </div>
  );
}
