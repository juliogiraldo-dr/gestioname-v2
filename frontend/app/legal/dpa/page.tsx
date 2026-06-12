import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Acuerdo de Encargado de Tratamiento (DPA) · Gestioname",
};

export default function DpaPage() {
  return (
    <>
      <h1 className="text-3xl font-semibold text-primary">Acuerdo de Encargado de Tratamiento (DPA)</h1>
      <p className="mt-2 text-sm text-ink-soft">Última actualización: 12 de junio de 2026</p>

      <div className="mt-8 space-y-6 text-sm leading-relaxed text-ink">
        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">1. Roles de las partes</h2>
          <p>
            El cliente (tu organización) actúa como <strong>responsable del tratamiento</strong> de los datos
            personales que introduce en la plataforma. Datarecover S.L. actúa como{" "}
            <strong>encargado del tratamiento</strong>, tratando dichos datos únicamente por cuenta y según las
            instrucciones del responsable.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">2. Objeto y duración</h2>
          <p>
            El objeto es el tratamiento de datos de empleados, socios y terceros necesario para prestar los servicios de
            RRHH, control de jornada, asociaciones y tesorería. El acuerdo permanece vigente mientras dure la relación
            contractual y la prestación del servicio.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">3. Medidas de seguridad</h2>
          <p>
            Aplicamos medidas técnicas y organizativas apropiadas: aislamiento de datos por organización (multi-tenant),
            cifrado de campos sensibles (IBAN, DNI), cifrado en tránsito (TLS), control de acceso por roles, registro de
            auditoría y copias de seguridad periódicas.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">4. Subencargados</h2>
          <p>
            Podemos recurrir a subencargados (por ejemplo, proveedores de alojamiento e infraestructura) que ofrezcan
            garantías equivalentes de protección de datos. Te informaremos de cualquier cambio previsto en la relación
            de subencargados para que puedas oponerte por motivos justificados.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">5. Confidencialidad y asistencia</h2>
          <p>
            El personal autorizado para tratar los datos está sujeto a deber de confidencialidad. Asistimos al
            responsable en el cumplimiento de sus obligaciones, incluida la atención de los derechos de los interesados
            y la notificación de violaciones de seguridad.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">6. Devolución y supresión</h2>
          <p>
            Finalizada la prestación del servicio, y a elección del responsable, devolveremos o suprimiremos los datos
            personales, salvo que la conservación venga exigida por la normativa aplicable (por ejemplo, plazos de
            conservación laboral y fiscal).
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">7. Contacto</h2>
          <p>
            Para cualquier cuestión relativa a este acuerdo, escribe a{" "}
            <a href="mailto:privacidad@datarecover.es" className="text-primary hover:underline">
              privacidad@datarecover.es
            </a>
            .
          </p>
        </section>
      </div>
    </>
  );
}
