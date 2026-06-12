"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Card, EmptyState, PageHeader, Skeleton } from "@/components/ui";

type Labor = {
  contract: {
    company: string | null;
    work_center: string | null;
    department: string | null;
    job_position: string | null;
    job_category: string | null;
    employment_status: string | null;
    hire_date: string | null;
  };
  agreement: { name: string; annual_hours: number; vacation_days: number; vacation_type: string } | null;
  schedule: { year: number; calendar: string } | null;
};

const STATUS_LABEL: Record<string, string> = { active: "Activo", inactive: "Inactivo", leave: "Excedencia" };

function Row({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex justify-between gap-4 border-b border-line py-2 last:border-0">
      <span className="text-sm text-ink-soft">{label}</span>
      <span className="text-sm font-medium text-ink">{value ?? "—"}</span>
    </div>
  );
}

export default function DatosLaboralesPage() {
  const [data, setData] = useState<Labor | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Labor }>("/me/labor");
        setData(res.data);
      } catch {
        setError(true);
      }
    })();
  }, []);

  if (error) {
    return (
      <div>
        <PageHeader title="Datos laborales" />
        <EmptyState title="Sin ficha de empleado" message="Tu usuario no tiene una ficha de empleado asociada." />
      </div>
    );
  }

  if (data === null) {
    return (
      <div>
        <PageHeader title="Datos laborales" subtitle="Tu contrato, convenio y horario" />
        <Skeleton rows={6} />
      </div>
    );
  }

  return (
    <div>
      <PageHeader title="Datos laborales" subtitle="Tu contrato, convenio y horario asignado" />
      <div className="grid gap-4 lg:grid-cols-3">
        <Card className="p-5">
          <h2 className="mb-3 text-sm font-semibold text-primary">Contrato</h2>
          <Row label="Empresa" value={data.contract.company} />
          <Row label="Centro" value={data.contract.work_center} />
          <Row label="Departamento" value={data.contract.department} />
          <Row label="Puesto" value={data.contract.job_position} />
          <Row label="Categoría" value={data.contract.job_category} />
          <Row label="Estado" value={data.contract.employment_status ? STATUS_LABEL[data.contract.employment_status] ?? data.contract.employment_status : null} />
          <Row label="Alta" value={data.contract.hire_date} />
        </Card>

        <Card className="p-5">
          <h2 className="mb-3 text-sm font-semibold text-primary">Convenio</h2>
          {data.agreement === null ? (
            <p className="text-sm text-ink-soft">Sin convenio asignado.</p>
          ) : (
            <>
              <Row label="Convenio" value={data.agreement.name} />
              <Row label="Horas anuales" value={data.agreement.annual_hours} />
              <Row label="Días de vacaciones" value={data.agreement.vacation_days} />
              <Row label="Tipo de vacaciones" value={data.agreement.vacation_type} />
            </>
          )}
        </Card>

        <Card className="p-5">
          <h2 className="mb-3 text-sm font-semibold text-primary">Horario {data.schedule?.year}</h2>
          {data.schedule === null ? (
            <p className="text-sm text-ink-soft">Sin calendario asignado este año.</p>
          ) : (
            <Row label="Calendario" value={data.schedule.calendar} />
          )}
        </Card>
      </div>
    </div>
  );
}
