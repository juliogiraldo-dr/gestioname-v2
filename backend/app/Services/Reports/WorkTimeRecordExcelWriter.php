<?php

declare(strict_types=1);

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * Escritor Excel del Registro de Jornada (ET 34.9). Consume la estructura de
 * {@see WorkTimeRecordService::compute()}.
 */
final class WorkTimeRecordExcelWriter
{
    private const HEADER_FILL = '0F2756';

    /**
     * Contenido binario .xlsx con una hoja por empleado.
     *
     * @param  array<string, mixed>  $data
     */
    public function build(array $data): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $options = $data['meta']['options'];

        foreach ($data['employees'] as $i => $employee) {
            $sheet = $spreadsheet->createSheet($i);
            $this->fillSheet($sheet, $employee, $data['meta'], $options);
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $spreadsheet->createSheet()->setTitle('Sin datos');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $this->toBinary($spreadsheet, $options['password'] ?? null);
    }

    /**
     * Un fichero .xlsx por empleado, empaquetados en un ZIP.
     *
     * @param  array<string, mixed>  $data
     */
    public function buildZip(array $data): string
    {
        $options = $data['meta']['options'];
        $zipPath = tempnam(sys_get_temp_dir(), 'wtr').'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($data['employees'] as $employee) {
            $single = $data;
            $single['employees'] = [$employee];

            $name = $this->slug($employee['employee']['full_name']);
            $zip->addFromString("registro-horario-{$name}.xlsx", $this->build($single));
        }

        $zip->close();
        $contents = (string) file_get_contents($zipPath);
        @unlink($zipPath);

        return $contents;
    }

    /**
     * @param  array<string, mixed>  $employee
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $options
     */
    private function fillSheet($sheet, array $employee, array $meta, array $options): void
    {
        $sheet->setTitle($this->sheetTitle($employee['employee']['full_name']));

        $sheet->setCellValue('A1', 'Registro de jornada (ET 34.9)');
        $sheet->setCellValue('A2', $employee['employee']['full_name']);
        $sheet->setCellValue('A3', "Periodo: {$meta['date_from']} a {$meta['date_to']}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $columns = $this->columns($options);
        $headerRow = 5;

        $col = 'A';
        foreach ($columns as $header) {
            $sheet->setCellValue($col.$headerRow, $header);
            $col++;
        }

        $this->styleHeader($sheet, $headerRow, count($columns));

        $row = $headerRow + 1;
        foreach ($employee['days'] as $day) {
            $this->writeDay($sheet, $row, $day, $options);
            $row++;
        }

        // Fila de totales.
        $this->writeTotals($sheet, $row, $employee['totals'], $options, count($columns));

        foreach (range('A', chr(ord('A') + count($columns) - 1)) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<string>
     */
    private function columns(array $options): array
    {
        $columns = ['Fecha', 'Horario'];

        if ($options['include_work_center']) {
            $columns[] = 'Centro';
        }

        $columns[] = 'Entrada';
        $columns[] = 'Salida';
        $columns[] = 'Previstas';
        $columns[] = 'Realizadas';
        $columns[] = 'Sobretiempo';

        if ($options['include_delays']) {
            $columns[] = 'Retraso (min)';
        }
        if ($options['include_method']) {
            $columns[] = 'Método';
        }
        if ($options['include_geolocation']) {
            $columns[] = 'Geolocalización';
        }

        return $columns;
    }

    /**
     * @param  array<string, mixed>  $day
     * @param  array<string, mixed>  $options
     */
    private function writeDay($sheet, int $row, array $day, array $options): void
    {
        $col = 'A';
        $put = function (string $value) use ($sheet, &$col, $row): void {
            $sheet->setCellValue($col.$row, $value);
            $col++;
        };

        $put($day['date']);
        $put((string) ($day['template'] ?? '—'));

        if ($options['include_work_center']) {
            $put((string) ($day['work_center'] ?? '—'));
        }

        $put((string) ($day['first_in'] ?? '—'));
        $put((string) ($day['last_out'] ?? '—'));
        $put($this->hours($day['expected'], $options));
        $put($this->hours($day['worked'], $options));
        $put($this->hours($day['overtime'], $options));

        if ($options['include_delays']) {
            $put((string) $day['delay_minutes']);
        }
        if ($options['include_method']) {
            $methods = collect($day['entries'])->pluck('method')->unique()->implode(', ');
            $put($methods ?: '—');
        }
        if ($options['include_geolocation']) {
            $geo = collect($day['entries'])
                ->filter(fn ($e) => $e['lat'] !== null && $e['lng'] !== null)
                ->map(fn ($e) => "{$e['lat']},{$e['lng']}")
                ->implode(' / ');
            $put($geo ?: '—');
        }
    }

    /**
     * @param  array<string, mixed>  $totals
     * @param  array<string, mixed>  $options
     */
    private function writeTotals($sheet, int $row, array $totals, array $options, int $columnCount): void
    {
        // La etiqueta "TOTAL" va en la primera columna; las horas en sus columnas.
        $sheet->setCellValue('A'.$row, 'TOTAL');

        // Localiza las columnas de Previstas/Realizadas/Sobretiempo dinámicamente.
        $columns = $this->columns($options);
        foreach (['Previstas' => $totals['expected'], 'Realizadas' => $totals['worked'], 'Sobretiempo' => $totals['overtime']] as $label => $value) {
            $idx = array_search($label, $columns, true);
            if ($idx !== false) {
                $sheet->setCellValue($this->colLetter((int) $idx).$row, $this->hours((float) $value, $options));
            }
        }

        if ($options['include_delays']) {
            $idx = array_search('Retraso (min)', $columns, true);
            if ($idx !== false) {
                $sheet->setCellValue($this->colLetter((int) $idx).$row, (string) $totals['delay_minutes']);
            }
        }

        $sheet->getStyle("A{$row}:".$this->colLetter($columnCount - 1).$row)
            ->getFont()->setBold(true);
    }

    private function styleHeader($sheet, int $row, int $columnCount): void
    {
        $range = "A{$row}:".$this->colLetter($columnCount - 1).$row;
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    /** Formatea horas decimales como HH:MM o como decimal según la opción. */
    private function hours(float $value, array $options): string
    {
        if ($options['decimal_format']) {
            return number_format($value, 2, ',', '.');
        }

        $totalMinutes = (int) round($value * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    private function toBinary(Spreadsheet $spreadsheet, ?string $password): string
    {
        if ($password !== null && $password !== '') {
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $protection = $sheet->getProtection();
                $protection->setSheet(true);
                $protection->setPassword($password);
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'wtr').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);
        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    private function colLetter(int $zeroBasedIndex): string
    {
        return Coordinate::stringFromColumnIndex($zeroBasedIndex + 1);
    }

    private function sheetTitle(string $name): string
    {
        // Excel: máx 31 chars y sin caracteres especiales.
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name) ?? $name;

        return mb_substr(trim($clean), 0, 31) ?: 'Empleado';
    }

    private function slug(string $name): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $name) ?? $name;

        return trim(mb_strtolower($slug), '-') ?: 'empleado';
    }
}
