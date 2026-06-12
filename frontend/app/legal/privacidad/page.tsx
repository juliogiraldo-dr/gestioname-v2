import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Política de privacidad · Gestioname",
};

export default function PrivacidadPage() {
  return (
    <>
      <h1 className="text-3xl font-semibold text-primary">Política de privacidad</h1>
      <p className="mt-2 text-sm text-ink-soft">Última actualización: 12 de junio de 2026</p>

      <div className="mt-8 space-y-6 text-sm leading-relaxed text-ink">
        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">1. Responsable del tratamiento</h2>
          <p>
            El responsable del tratamiento de tus datos es <strong>Datarecover S.L.</strong>, con domicilio en
            Majadahonda (Madrid). Para cualquier cuestión relativa a la protección de datos puedes escribir a{" "}
            <a href="mailto:privacidad@datarecover.es" className="text-primary hover:underline">
              privacidad@datarecover.es
            </a>
            .
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">2. Datos que recogemos</h2>
          <p>
            Tratamos los datos que nos facilitas al registrarte y usar la plataforma: datos de contacto de la
            organización y del administrador (nombre, email, subdominio), así como los datos que tú introduzcas para
            gestionar empleados, socios o tesorería. Algunos campos sensibles (IBAN, DNI) se almacenan cifrados.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">3. Finalidad</h2>
          <p>
            Utilizamos los datos para prestar el servicio contratado: control de jornada, gestión de RRHH, gestión de
            asociaciones y tesorería, así como para la administración de tu cuenta y la facturación del servicio.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">4. Base legal</h2>
          <p>
            La base legal es la ejecución del contrato de prestación del servicio, el cumplimiento de obligaciones
            legales (por ejemplo, el registro de jornada conforme al art. 34.9 del Estatuto de los Trabajadores) y, en
            su caso, tu consentimiento.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">5. Conservación</h2>
          <p>
            Conservamos tus datos mientras la cuenta esté activa. Los registros de jornada y fichajes se conservan un
            mínimo de 4 años, según exige la normativa laboral. Tras la baja, los datos se bloquean y se eliminan
            transcurridos los plazos legales de prescripción.
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">6. Tus derechos</h2>
          <p>
            Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación del tratamiento y
            portabilidad (derechos ARCO/RGPD) escribiendo a{" "}
            <a href="mailto:privacidad@datarecover.es" className="text-primary hover:underline">
              privacidad@datarecover.es
            </a>
            . También tienes derecho a presentar una reclamación ante la Agencia Española de Protección de Datos
            (AEPD).
          </p>
        </section>

        <section>
          <h2 className="mb-2 text-lg font-semibold text-primary">7. Encargado de tratamiento</h2>
          <p>
            Cuando actuamos como encargado del tratamiento de los datos que gestionas en la plataforma, se aplica el{" "}
            <a href="/legal/dpa" className="text-primary hover:underline">
              Acuerdo de Encargado de Tratamiento (DPA)
            </a>
            .
          </p>
        </section>
      </div>
    </>
  );
}
