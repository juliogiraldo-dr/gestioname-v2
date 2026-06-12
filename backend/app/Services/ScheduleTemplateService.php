<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ScheduleTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de plantillas de horario.
 */
final class ScheduleTemplateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScheduleTemplate
    {
        $ranges = $data['time_ranges'] ?? null;
        unset($data['time_ranges']);

        return DB::transaction(function () use ($data, $ranges): ScheduleTemplate {
            $template = ScheduleTemplate::create($data);
            $this->syncRanges($template, $ranges);

            return $template;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ScheduleTemplate $template, array $data): ScheduleTemplate
    {
        $hasRanges = array_key_exists('time_ranges', $data);
        $ranges = $data['time_ranges'] ?? [];
        unset($data['time_ranges']);

        return DB::transaction(function () use ($template, $data, $hasRanges, $ranges): ScheduleTemplate {
            $template->update($data);

            if ($hasRanges) {
                $template->timeRanges()->delete();
                $this->syncRanges($template, $ranges);
            }

            return $template;
        });
    }

    public function delete(ScheduleTemplate $template): void
    {
        $template->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $ranges
     */
    private function syncRanges(ScheduleTemplate $template, ?array $ranges): void
    {
        if (empty($ranges)) {
            return;
        }

        foreach (array_values($ranges) as $i => $range) {
            $template->timeRanges()->create([
                'time_start' => $range['time_start'],
                'time_end' => $range['time_end'],
                'sort_order' => $range['sort_order'] ?? $i,
            ]);
        }
    }
}
