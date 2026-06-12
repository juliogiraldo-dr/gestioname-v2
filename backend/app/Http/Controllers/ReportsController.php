<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Report\DailyAttendanceReportRequest;
use App\Http\Requests\Report\LeaveSummaryReportRequest;
use App\Http\Requests\Report\WorkTimeRecordReportRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\Reports\LeaveSummaryReportService;
use App\Services\Reports\WorkTimeRecordExcelWriter;
use App\Services\Reports\WorkTimeRecordPdfWriter;
use App\Services\Reports\WorkTimeRecordService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Informes del registro horario (ET 34.9), diario y resumen de ausencias.
 *
 * Los ficheros se devuelven como descarga directa (Content-Disposition: attachment).
 */
class ReportsController extends Controller
{
    public function __construct(
        private readonly WorkTimeRecordService $workTimeRecord,
        private readonly WorkTimeRecordExcelWriter $excel,
        private readonly WorkTimeRecordPdfWriter $pdf,
        private readonly LeaveSummaryReportService $leaveSummary,
    ) {}

    /** Registro horario ET 34.9 en Excel o PDF (ZIP por empleado si split_by_employee). */
    public function workTimeRecord(WorkTimeRecordReportRequest $request): Response|StreamedResponse
    {
        $params = $request->validated();
        $data = $this->workTimeRecord->compute($params);

        $split = (bool) ($params['options']['split_by_employee'] ?? false);
        $format = $params['format'];

        [$contents, $mime, $filename] = match (true) {
            $format === 'excel' && $split => [$this->excel->buildZip($data), 'application/zip', 'registro-horario.zip'],
            $format === 'excel' => [$this->excel->build($data), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'registro-horario.xlsx'],
            $format === 'pdf' && $split => [$this->pdf->buildZip($data), 'application/zip', 'registro-horario.zip'],
            default => [$this->pdf->build($data), 'application/pdf', 'registro-horario.pdf'],
        };

        return $this->download($contents, $mime, $filename);
    }

    /** Informe diario: JSON (por defecto) o PDF. */
    public function dailyAttendance(DailyAttendanceReportRequest $request): AnonymousResourceCollection|Response
    {
        $validated = $request->validated();

        $query = Attendance::query()
            ->whereDate('clocked_at', $validated['date'])
            ->when(! empty($validated['company_id']), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('company_id', $validated['company_id'])))
            ->when(! empty($validated['work_center_id']), fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('work_center_id', $validated['work_center_id'])))
            ->with(['employee', 'milestone'])
            ->orderBy('clocked_at');

        if (($validated['format'] ?? 'json') === 'pdf') {
            $attendances = $query->get();
            $contents = Pdf::loadView('reports.daily-attendance', [
                'date' => $validated['date'],
                'attendances' => $attendances,
            ])->output();

            return $this->download($contents, 'application/pdf', "informe-diario-{$validated['date']}.pdf");
        }

        // Un solo día está acotado: devolvemos toda la colección (la consume la barra visual).
        return AttendanceResource::collection($query->get());
    }

    /** Resumen de ausencias del año en Excel. */
    public function leaveSummary(LeaveSummaryReportRequest $request): Response|StreamedResponse
    {
        $contents = $this->leaveSummary->excel($request->validated());

        return $this->download(
            $contents,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            "resumen-ausencias-{$request->validated('year')}.xlsx",
        );
    }

    private function download(string $contents, string $mime, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print ($contents),
            $filename,
            ['Content-Type' => $mime],
        );
    }
}
