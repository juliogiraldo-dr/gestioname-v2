<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Importación de empleados desde Excel y generación de la plantilla descargable.
 */
final class EmployeeImportService
{
    /** Columnas de la plantilla, en orden. La primera fila del Excel son estas cabeceras. */
    public const COLUMNS = [
        'first_name', 'last_name', 'second_last_name', 'dni', 'birth_date',
        'email_personal', 'phone_personal', 'department', 'job_position', 'hire_date',
    ];

    public function __construct(private readonly EmployeeService $employees) {}

    /** Contenido binario (.xlsx) de la plantilla con la fila de cabeceras. */
    public function templateContents(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray(self::COLUMNS, null, 'A1');

        return $this->save($spreadsheet);
    }

    /**
     * Exporta empleados a .xlsx.
     *
     * @param  Collection<int, Employee>  $employees
     */
    public function export(Collection $employees): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Nombre', 'Apellidos', 'DNI', 'Departamento', 'Puesto', 'Email', 'Fecha alta', 'Activo'], null, 'A1');

        $r = 2;
        foreach ($employees as $e) {
            $sheet->fromArray([
                $e->first_name, $e->last_name, $e->dni, $e->department, $e->job_position,
                $e->email_personal, $e->hire_date?->toDateString(), $e->active ? 'Sí' : 'No',
            ], null, 'A'.$r);
            $r++;
        }
        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        return $this->save($spreadsheet);
    }

    private function save(Spreadsheet $spreadsheet): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'emp').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);
        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    /**
     * Importa empleados de un fichero Excel para una empresa.
     *
     * @return array{imported: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(string $companyId, string $filePath): array
    {
        $rows = IOFactory::load($filePath)->getActiveSheet()->toArray();
        $header = array_map(static fn ($h) => trim((string) $h), $rows[0] ?? []);

        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2; // +2: 1 por la cabecera, 1 porque el índice es base 0

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));

            if (empty($data['first_name']) || empty($data['last_name'])) {
                $errors[] = ['row' => $line, 'message' => 'first_name y last_name son obligatorios.'];

                continue;
            }

            try {
                $this->employees->create([
                    'company_id' => $companyId,
                    'first_name' => (string) $data['first_name'],
                    'last_name' => (string) $data['last_name'],
                    'second_last_name' => $data['second_last_name'] ? (string) $data['second_last_name'] : null,
                    'dni' => $data['dni'] ? (string) $data['dni'] : null,
                    'birth_date' => $this->date($data['birth_date'] ?? null),
                    'email_personal' => $data['email_personal'] ? (string) $data['email_personal'] : null,
                    'phone_personal' => $data['phone_personal'] ? (string) $data['phone_personal'] : null,
                    'department' => $data['department'] ? (string) $data['department'] : null,
                    'job_position' => $data['job_position'] ? (string) $data['job_position'] : null,
                    'hire_date' => $this->date($data['hire_date'] ?? null),
                ]);
                $imported++;
            } catch (Throwable $e) {
                $errors[] = ['row' => $line, 'message' => $e->getMessage()];
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
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
