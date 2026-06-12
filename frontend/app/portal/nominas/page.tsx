import { Card, PageHeader } from "@/components/ui";

export default function NominasPage() {
  return (
    <div>
      <PageHeader title="Mis nóminas" subtitle="Descarga tus recibos de nómina" />
      <Card className="p-6">
        <p className="text-sm text-ink-soft">
          Aquí aparecerán tus nóminas cuando RRHH las publique (módulo previsto en la Fase 2).
        </p>
      </Card>
    </div>
  );
}
