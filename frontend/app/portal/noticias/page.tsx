import { Card, PageHeader } from "@/components/ui";

export default function NoticiasPage() {
  return (
    <div>
      <PageHeader title="Noticias" subtitle="Comunicaciones de la empresa" />
      <Card className="p-6">
        <p className="text-sm text-ink-soft">No hay noticias publicadas.</p>
      </Card>
    </div>
  );
}
