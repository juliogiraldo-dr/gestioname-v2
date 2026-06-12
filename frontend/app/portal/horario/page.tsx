"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import { Card, PageHeader, Spinner } from "@/components/ui";

type Day = { date: string; template: string | null; color: string | null; hours: number | null };
type Schedule = { year: number; calendar: { name: string; days: Day[] } | null };

export default function HorarioPage() {
  const [schedule, setSchedule] = useState<Schedule | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void (async () => {
      try {
        const res = await api<{ data: Schedule }>("/me/schedule");
        setSchedule(res.data);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  if (loading) return <Spinner />;

  const calendar = schedule?.calendar;

  return (
    <div>
      <PageHeader title="Mi horario" subtitle={`Calendario laboral ${schedule?.year ?? ""}`} />
      {!calendar ? (
        <Card className="p-6">
          <p className="text-sm text-ink-soft">No tienes un calendario asignado este año.</p>
        </Card>
      ) : (
        <Card className="p-6">
          <h2 className="mb-4 text-sm font-semibold text-primary">{calendar.name}</h2>
          <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            {calendar.days.slice(0, 60).map((d) => (
              <div key={d.date} className="flex items-center gap-2 rounded-[var(--radius-fluent)] border border-line px-3 py-2 text-sm">
                <span className="inline-block h-3 w-3 shrink-0 rounded-full" style={{ background: d.color ?? "#cbd5e1" }} />
                <span className="text-ink-soft">{d.date}</span>
                <span className="ml-auto font-medium text-primary">{d.hours ?? "—"}h</span>
              </div>
            ))}
          </div>
          {calendar.days.length > 60 && (
            <p className="mt-4 text-xs text-ink-soft">Mostrando los primeros 60 días de {calendar.days.length}.</p>
          )}
        </Card>
      )}
    </div>
  );
}
