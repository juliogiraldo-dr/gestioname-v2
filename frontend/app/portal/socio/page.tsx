"use client";

import { useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import { formatDate } from "@/lib/utils";
import { Badge, Card, EmptyState, PageHeader, Skeleton } from "@/components/ui";

type Member = {
  full_name: string;
  member_number: string;
  status: string;
  email: string | null;
  phone: string | null;
  member_type: string | null;
  entity: { name: string; type: string | null; email: string | null; phone: string | null } | null;
};

type Payment = {
  year: number;
  amount: number;
  status: string;
  payment_date: string | null;
  payment_method: string | null;
};

const eur = new Intl.NumberFormat("es-ES", { style: "currency", currency: "EUR" });

/** Etiqueta + tono del estado de un pago/cuota. */
function paymentBadge(status: string): { label: string; tone: "ok" | "warn" | "neutral" } {
  switch (status) {
    case "pagado":
      return { label: "Pagado", tone: "ok" };
    case "parcial":
      return { label: "Parcial", tone: "warn" };
    default:
      return { label: "Pendiente", tone: "neutral" };
  }
}

/** Tono del estado del socio. */
function statusTone(status: string): "ok" | "warn" | "neutral" {
  if (status === "activo" || status === "active") return "ok";
  if (status === "baja" || status === "inactivo") return "neutral";
  return "warn";
}

export default function PortalSocioPage() {
  const [member, setMember] = useState<Member | null>(null);
  const [payments, setPayments] = useState<Payment[]>([]);
  const [loading, setLoading] = useState(true);
  const [noProfile, setNoProfile] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const [m, p] = await Promise.all([
          api<{ data: Member }>("/me/member"),
          api<{ data: Payment[] }>("/me/member/payments"),
        ]);
        setMember(m.data);
        setPayments(p.data);
      } catch (e) {
        if (e instanceof ApiError && (e.code === "NO_MEMBER_PROFILE" || e.status === 404)) {
          setNoProfile(true);
        } else {
          setError(e instanceof ApiError ? e.message : "No se pudo cargar tu ficha de socio.");
        }
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) {
    return (
      <div>
        <PageHeader title="Mi cuota" subtitle="Consulta tu ficha de socio y tus pagos" />
        <Skeleton rows={6} />
      </div>
    );
  }

  if (noProfile || !member) {
    return (
      <div>
        <PageHeader title="Mi cuota" subtitle="Consulta tu ficha de socio y tus pagos" />
        <EmptyState
          title="Sin ficha de socio"
          message={error ?? "No tienes una ficha de socio asociada."}
        />
      </div>
    );
  }

  const currentYear = new Date().getFullYear();
  const currentPayment = payments.find((p) => p.year === currentYear) ?? null;
  const sBadge = statusTone(member.status);

  return (
    <div>
      <PageHeader title="Mi cuota" subtitle="Consulta tu ficha de socio y tus pagos" />

      {/* Ficha del socio */}
      <Card className="mb-6 p-5">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 className="text-lg font-semibold text-primary">{member.full_name}</h2>
            <p className="mt-1 text-sm text-ink-soft">
              Nº socio: <span className="font-medium text-ink">{member.member_number}</span>
              {member.member_type ? <> · {member.member_type}</> : null}
            </p>
          </div>
          <Badge tone={sBadge}>{member.status}</Badge>
        </div>
        <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-ink-soft">Email</dt>
            <dd className="text-ink">{member.email ?? "—"}</dd>
          </div>
          <div>
            <dt className="text-ink-soft">Teléfono</dt>
            <dd className="text-ink">{member.phone ?? "—"}</dd>
          </div>
        </dl>
      </Card>

      {/* Cuota del año actual */}
      <Card className="mb-6 p-5">
        <h3 className="text-base font-semibold text-primary">Mi cuota {currentYear}</h3>
        {currentPayment ? (
          <div className="mt-3 flex flex-wrap items-center gap-4">
            <span className="text-3xl font-semibold text-primary">{eur.format(currentPayment.amount)}</span>
            <Badge tone={paymentBadge(currentPayment.status).tone}>
              {paymentBadge(currentPayment.status).label}
            </Badge>
            {currentPayment.payment_date && (
              <span className="text-sm text-ink-soft">Pagada el {formatDate(currentPayment.payment_date)}</span>
            )}
          </div>
        ) : (
          <p className="mt-2 text-sm text-ink-soft">Sin cuota registrada.</p>
        )}
      </Card>

      {/* Histórico de pagos */}
      <Card className="mb-6 overflow-hidden">
        <div className="border-b border-line px-5 py-3">
          <h3 className="text-base font-semibold text-primary">Histórico de pagos</h3>
        </div>
        {payments.length === 0 ? (
          <p className="px-5 py-6 text-sm text-ink-soft">No hay pagos registrados.</p>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-ink-soft">
                <th className="px-5 py-2 font-medium">Año</th>
                <th className="px-5 py-2 font-medium">Importe</th>
                <th className="px-5 py-2 font-medium">Estado</th>
                <th className="px-5 py-2 font-medium">Fecha</th>
              </tr>
            </thead>
            <tbody>
              {payments.map((p) => {
                const b = paymentBadge(p.status);
                return (
                  <tr key={p.year} className="border-b border-line last:border-0">
                    <td className="px-5 py-2.5 text-ink">{p.year}</td>
                    <td className="px-5 py-2.5 text-ink">{eur.format(p.amount)}</td>
                    <td className="px-5 py-2.5">
                      <Badge tone={b.tone}>{b.label}</Badge>
                    </td>
                    <td className="px-5 py-2.5 text-ink-soft">{formatDate(p.payment_date)}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        )}
      </Card>

      {/* Entidad */}
      {member.entity && (
        <Card className="p-5">
          <h3 className="text-base font-semibold text-primary">Mi entidad</h3>
          <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt className="text-ink-soft">Nombre</dt>
              <dd className="text-ink">{member.entity.name}</dd>
            </div>
            <div>
              <dt className="text-ink-soft">Tipo</dt>
              <dd className="text-ink">{member.entity.type ?? "—"}</dd>
            </div>
            <div>
              <dt className="text-ink-soft">Email</dt>
              <dd className="text-ink">{member.entity.email ?? "—"}</dd>
            </div>
            <div>
              <dt className="text-ink-soft">Teléfono</dt>
              <dd className="text-ink">{member.entity.phone ?? "—"}</dd>
            </div>
          </dl>
        </Card>
      )}
    </div>
  );
}
