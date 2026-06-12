import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Términos del servicio · Gestioname",
};

export default function TerminosPage() {
  return (
    <>
      <h1 className="text-3xl font-semibold text-primary">Términos del servicio</h1>
      <p className="mt-2 text-sm text-ink-soft">Última actualización: 12 de junio de 2026</p>

      <div className="mt-8 space-y-6 text-sm leading-relaxed text-ink">
        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">1. Descripción del servicio</h2>
          <p>
            Gestioname es una plataforma SaaS de Datarecover S.L. para la gestión de RRHH, control de jornada, gestión
            de asociaciones y tesorería. El servicio se presta en modalidad multi-tenant, con un espacio aislado por
            organización.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">2. Uso aceptable</h2>
          <p>
            Te comprometes a usar la plataforma conforme a la ley, a no realizar accesos no autorizados ni intentar
            comprometer la seguridad del servicio, y a no introducir datos de terceros sin la base legal adecuada. Eres
            responsable de la confidencialidad de las credenciales de acceso de tu organización.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">3. Disponibilidad (SLA)</h2>
          <p>
            Procuramos una disponibilidad objetivo del 99,5% mensual, excluidas las ventanas de mantenimiento
            programado, que se comunicarán con antelación razonable. El mantenimiento de emergencia podrá realizarse sin
            preaviso cuando sea necesario para la seguridad del servicio.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">4. Cancelación</h2>
          <p>
            Puedes cancelar tu suscripción en cualquier momento desde el panel de administración. La cancelación surte
            efecto al final del período de facturación en curso, sin derecho a reembolso de los importes ya abonados,
            salvo disposición legal en contrario.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">5. Portabilidad de datos</h2>
          <p>
            En cualquier momento puedes exportar tus datos en formatos estándar (Excel, PDF y ficheros de enlace
            contable). Tras la baja, dispondrás de un plazo razonable para descargar tu información antes de su
            eliminación definitiva.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">6. Limitación de responsabilidad</h2>
          <p>
            En la medida permitida por la ley, Datarecover S.L. no será responsable de daños indirectos, lucro cesante
            o pérdida de datos derivados de un uso incorrecto del servicio. Nuestra responsabilidad agregada quedará
            limitada a los importes abonados por el servicio en los doce meses anteriores al hecho que origine la
            reclamación.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">7. Protección de datos</h2>
          <p>
            El tratamiento de datos personales se rige por nuestra{" "}
            <a href="/legal/privacidad" className="text-primary hover:underline">
              Política de privacidad
            </a>{" "}
            y, cuando proceda, por el{" "}
            <a href="/legal/dpa" className="text-primary hover:underline">
              Acuerdo de Encargado de Tratamiento
            </a>
            .
          </p>
        </section>
      </div>
    </>
  );
}
