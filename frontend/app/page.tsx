"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";
import { Spinner } from "@/components/ui";
import { Landing } from "@/components/Landing";

const ADMIN_ROLES = ["admin", "rrhh-coordinator"];

/** Enruta por rol; si no hay sesión, muestra la landing pública con registro. */
export default function Home() {
  const { profile, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (loading || !profile) return;
    if (profile.roles.includes("super-admin")) router.replace("/superadmin");
    else if (profile.roles.some((r) => ADMIN_ROLES.includes(r))) router.replace("/admin");
    // La gestoría externa solo accede a su panel (nóminas/descargas e informes).
    else if (profile.roles.includes("gestoria")) router.replace("/admin/gestoria");
    // El socio (sin perfil de empleado/admin/gestoría) va a su portal de cuota.
    else if (profile.roles.includes("member")) router.replace("/portal/socio");
    else router.replace("/portal");
  }, [loading, profile, router]);

  if (loading) {
    return (
      <div className="flex min-h-full items-center justify-center">
        <Spinner />
      </div>
    );
  }

  if (profile) {
    return (
      <div className="flex min-h-full items-center justify-center">
        <Spinner />
      </div>
    );
  }

  return <Landing />;
}
