<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

/**
 * Escritor PDF del Registro de Jornada (ET 34.9). Renderiza la vista Blade
 * `reports.work-time-record` y la convierte a PDF con DomPDF.
 */
final class WorkTimeRecordPdfWriter
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function build(array $data): string
    {
        return Pdf::loadView('reports.work-time-record', [
            'meta' => $data['meta'],
            'employees' => $data['employees'],
            'totals' => $data['totals'],
            'fmt' => fn (float $v) => $this->hours($v, $data['meta']['options']),
        ])->setPaper('a4', 'landscape')->output();
    }

    /**
     * Un PDF por empleado dentro de un ZIP.
     *
     * @param  array<string, mixed>  $data
     */
    public function buildZip(array $data): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'wtr').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($data['employees'] as $employee) {
            $single = $data;
            $single['employees'] = [$employee];
            $name = $this->slug($employee['employee']['full_name']);
            $zip->addFromString("registro-horario-{$name}.pdf", $this->build($single));
        }

        $zip->close();
        $contents = (string) file_get_contents($zipPath);
        @unlink($zipPath);

        return $contents;
    }

    private function hours(float $value, array $options): string
    {
        if ($options['decimal_format']) {
            return number_format($value, 2, ',', '.');
        }

        $totalMinutes = (int) round($value * 60);

        return sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }

    private function slug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name;

        return trim(mb_strtolower($slug), '-') ?: 'empleado';
    }
}
