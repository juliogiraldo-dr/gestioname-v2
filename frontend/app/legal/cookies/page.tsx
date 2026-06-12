import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Política de cookies · Gestioname",
};

export default function CookiesPage() {
  return (
    <>
      <h1 className="text-3xl font-semibold text-primary">Política de cookies</h1>
      <p className="mt-2 text-sm text-ink-soft">Última actualización: 12 de junio de 2026</p>

      <div className="mt-8 space-y-6 text-sm leading-relaxed text-ink">
        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">1. Solo almacenamiento técnico</h2>
          <p>
            Gestioname utiliza exclusivamente cookies y almacenamiento técnico necesario para el funcionamiento de la
            plataforma. <strong>No usamos cookies de marketing, publicidad ni rastreo de terceros.</strong>
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">2. Qué almacenamos</h2>
          <ul className="list-disc space-y-1 pl-5">
            <li>
              <strong>Token de sesión</strong>: para mantener tu acceso iniciado de forma segura.
            </li>
            <li>
              <strong>Empresa o entidad activa</strong>: para recordar el contexto sobre el que trabajas.
            </li>
            <li>
              <strong>Preferencias de interfaz</strong>: como la aceptación de este aviso de cookies.
            </li>
          </ul>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">3. Gestión</h2>
          <p>
            Al tratarse de almacenamiento estrictamente necesario, no requiere consentimiento previo. Puedes eliminar
            estos datos en cualquier momento borrando el almacenamiento local y las cookies desde la configuración de tu
            navegador, si bien ello puede afectar al correcto funcionamiento del servicio.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">4. Más información</h2>
          <p>
            Para más detalles sobre el tratamiento de datos personales, consulta nuestra{" "}
            <a href="/legal/privacidad" className="text-primary hover:underline">
              Política de privacidad
            </a>
            .
          </p>
        </section>
      </div>
    </>
  );
}
