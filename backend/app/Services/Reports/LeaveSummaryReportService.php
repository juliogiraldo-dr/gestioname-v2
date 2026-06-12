<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Informe resumen de ausencias por empleado: vacaciones disponibles, días solicitados,
 * aprobados, rechazados y en espera dentro de un año.
 */
final class LeaveSummaryReportService
{
    /**
     * @param  array{year: int, company_id?: ?string, work_center_ids?: list<string>, employee_ids?: list<string>}  $params
     * @return list<array<string, mixed>>
     */
    public function compute(array $params): array
    {
        $year = $params['year'];
        $employees = $this->resolveEmployees($params);

        return $employees->map(function (Employee $employee) use ($year): array {
            $base = LeaveRequest::where('employee_id', $employee->id)->whereYear('date_start', $year);

            $available = (int) ($employee->agreement?->vacation_days ?? 0);
            $approved = (float) (clone $base)->where('status', 'aprobada')->sum('total_days');

            return [
                'full_name' => $employee->fullName(),
                'department' => $employee->department,
                'available' => $available,
                'requested' => (float) (clone $base)->where('status', 'pendiente')->sum('total_days'),
                'approved' => $approved,
                'rejected' => (float) (clone $base)->where('status', 'rechazada')->sum('total_days'),
                'pending_count' => (clone $base)->where('status', 'pendiente')->count(),
                'remaining' => $available - $approved,
            ];
        })->all();
    }

    /**
     * @param  array{year: int, company_id?: ?string, work_center_ids?: list<string>, employee_ids?: list<string>}  $params
     */
    public function excel(array $params): string
    {
        $rows = $this->compute($params);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Ausencias {$params['year']}");

        $sheet->setCellValue('A1', "Resumen de ausencias — {$params['year']}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Empleado', 'Departamento', 'Disponibles', 'Solicitados', 'Aprobados', 'Rechazados', 'En espera', 'Restantes'];
        $sheet->fromArray($headers, null, 'A3');

        $style = $sheet->getStyle('A3:H3');
        $style->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F2756');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 4;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $r['full_name'], $r['department'] ?? '—',
                $r['available'], $r['requested'], $r['approved'],
                $r['rejected'], $r['pending_count'], $r['remaining'],
            ], null, 'A'.$row);
            $row++;
        }

        foreach (range('A', 'H') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'leave').'.xlsx';
        (new Xlsx($spreadsheet))->save($tmp);
        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    /**
     * @return Collection<int, Employee>
     */
    private function resolveEmployees(array $params): Collection
    {
        return Employee::query()
            ->when(! empty($params['company_id']), fn ($q) => $q->where('company_id', $params['company_id']))
            ->when(! empty($params['work_center_ids']), fn ($q) => $q->whereIn('work_center_id', $params['work_center_ids']))
            ->when(! empty($params['employee_ids']), fn ($q) => $q->whereIn('id', $params['employee_ids']))
            ->with('agreement')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
