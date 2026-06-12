"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { Spinner } from "@/components/ui";

/** Empresas se gestiona ahora dentro de Configuración. */
export default function EmpresasRedirect() {
  const router = useRouter();
  useEffect(() => {
    router.replace("/admin/configuracion");
  }, [router]);
  return (
    <div className="flex min-h-full items-center justify-center">
      <Spinner />
    </div>
  );
}
