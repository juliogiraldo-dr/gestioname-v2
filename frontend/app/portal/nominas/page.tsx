"use client";

import { useEffect, useState } from "react";
import { api, downloadFile } from "@/lib/api";
import { Button, Card, EmptyState, PageHeader, Skeleton } from "@/components/ui";

type Payslip = {
  id: string;
  month: number;
  year: number;
  period: string;
  created_at: string | null;
};

export default function NominasPage() {
  const [payslips, setPayslips] = useState<Payslip[] | null>(null);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Payslip[] }>("/me/payslips");
        setPayslips(res.data);
      } catch {
        setPayslips([]);
      }
    })();
  }, []);

  return (
    <div>
      <PageHeader title="Mis nóminas" subtitle="Consulta y descarga tus recibos de nómina" />
      {payslips === null ? (
        <Skeleton rows={4} />
      ) : payslips.length === 0 ? (
        <EmptyState
          title="Aún no tienes nóminas"
          message="Cuando RRHH o tu gestoría publiquen una nómina, aparecerá aquí y recibirás un aviso por email."
        />
      ) : (
        <Card className="divide-y divide-line">
          {payslips.map((p) => (
            <div key={p.id} className="flex items-center justify-between p-4">
              <div>
                <p className="font-medium capitalize text-ink">{p.period}</p>
                {p.created_at && (
                  <p className="text-xs text-ink-soft">
                    Publicada el {new Date(p.created_at).toLocaleDateString("es-ES")}
                  </p>
                )}
              </div>
              <Button
                variant="secondary"
                onClick={() =>
                  downloadFile(`/me/payslips/${p.id}/download`, {
                    method: "GET",
                    fallbackName: `nomina-${p.year}-${String(p.month).padStart(2, "0")}.pdf`,
                  })
                }
              >
                Descargar PDF
              </Button>
            </div>
          ))}
        </Card>
      )}
    </div>
  );
}
