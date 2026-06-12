import { Card, PageHeader } from "@/components/ui";

export default function DocumentosPage() {
  return (
    <div>
      <PageHeader title="Mis documentos" subtitle="Documentación compartida contigo" />
      <Card className="p-6">
        <p className="text-sm text-ink-soft">No tienes documentos disponibles por el momento.</p>
      </Card>
    </div>
  );
}
