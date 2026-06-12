<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use App\Models\Member;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Importación/exportación de socios desde/hacia Excel (.xlsx).
 */
final class SocioImportService
{
    /** Columnas de la plantilla, en orden. */
    public const COLUMNS = ['first_name', 'last_name', 'dni', 'email', 'phone', 'member_number', 'status', 'date_join'];

    public function __construct(private readonly MemberService $members) {}

    /** Plantilla .xlsx con la fila de cabeceras. */
    public function templateContents(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(self::COLUMNS, null, 'A1');

        return $this->toBinary($spreadsheet);
    }

    /**
     * @return array{imported: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(Entity $entity, string $filePath): array
    {
        $rows = IOFactory::load($filePath)->getActiveSheet()->toArray();
        $header = array_map(static fn ($h) => trim((string) $h), $rows[0] ?? []);

        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $data = array_combine($header, array_pad($row, count($header), null));

            if (empty($data['first_name'])) {
                $errors[] = ['row' => $line, 'message' => 'first_name es obligatorio.'];

                continue;
            }

            try {
                $this->members->create($entity, [
                    'first_name' => (string) $data['first_name'],
                    'last_name' => $data['last_name'] ? (string) $data['last_name'] : null,
                    'dni' => $data['dni'] ? (string) $data['dni'] : null,
                    'email' => $data['email'] ? (string) $data['email'] : null,
                    'phone' => $data['phone'] ? (string) $data['phone'] : null,
                    'member_number' => $data['member_number'] ? (string) $data['member_number'] : null,
                    'status' => in_array($data['status'] ?? null, ['activo', 'baja_voluntaria', 'baja_impagada', 'honor', 'pendiente'], true) ? $data['status'] : 'activo',
                    'date_join' => $this->date($data['date_join'] ?? null),
                ]);
                $imported++;
            } catch (Throwable $e) {
                $errors[] = ['row' => $line, 'message' => $e->getMessage()];
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * @param  Collection<int, Member>  $members
     */
    public function export(Collection $members): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nº', 'Nombre', 'Apellidos', 'DNI', 'Email', 'Teléfono', 'Tipo', 'Estado', 'Alta'], null, 'A1');

        $r = 2;
        foreach ($members as $m) {
            $sheet->fromArray([
                $m->member_number, $m->first_name, $m->last_name, $m->dni, $m->email, $m->phone,
                $m->memberType?->name, $m->status, $m->date_join?->toDateString(),
            ], null, 'A'.$r);
            $r++;
        }
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        return $this->toBinary($spreadsheet);
    }

    private function toBinary(Spreadsheet $spreadsheet): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'soc').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);
        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
