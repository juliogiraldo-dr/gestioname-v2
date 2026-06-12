<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Services\Socios\EntityBackupService;
use App\Services\Socios\SocioImportService;
use App\Services\Socios\SocioPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SocioToolsController extends Controller
{
    public function __construct(
        private readonly SocioPdfService $pdf,
        private readonly SocioImportService $excel,
        private readonly EntityBackupService $backup,
    ) {}

    // ── PDFs ───────────────────────────────────────────────
    public function receipt(MemberPayment $memberPayment): StreamedResponse
    {
        return $this->download($this->pdf->receipt($memberPayment), 'application/pdf', "recibo-{$memberPayment->year}.pdf");
    }

    public function card(Member $member): StreamedResponse
    {
        return $this->download($this->pdf->memberCard($member), 'application/pdf', "carnet-{$member->member_number}.pdf");
    }

    public function sheet(Member $member): StreamedResponse
    {
        return $this->download($this->pdf->memberSheet($member), 'application/pdf', "ficha-socio-{$member->member_number}.pdf");
    }

    public function membersPdf(Request $request, Entity $entity): StreamedResponse
    {
        return $this->download($this->pdf->membersList($entity, $this->filtered($request, $entity)), 'application/pdf', 'socios.pdf');
    }

    // ── Excel ──────────────────────────────────────────────
    public function template(): StreamedResponse
    {
        return $this->download($this->excel->templateContents(), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'plantilla-socios.xlsx');
    }

    public function import(Request $request, Entity $entity): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);
        $result = $this->excel->import($entity, $request->file('file')->getRealPath());

        return response()->json(['data' => $result]);
    }

    public function export(Request $request, Entity $entity): StreamedResponse
    {
        return $this->download($this->excel->export($this->filtered($request, $entity)), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'socios.xlsx');
    }

    // ── Backup JSON ────────────────────────────────────────
    public function backupExport(Entity $entity): StreamedResponse
    {
        $json = json_encode($this->backup->export($entity), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';

        return $this->download($json, 'application/json', "backup-{$entity->name}.json");
    }

    public function backupImport(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:json,txt', 'max:10240']]);
        $data = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        abort_if(! is_array($data) || ! isset($data['entity']), 422, 'Backup no válido.');

        $entity = $this->backup->import($data);

        return response()->json(['data' => ['id' => $entity->id, 'name' => $entity->name]], 201);
    }

    /**
     * @return Collection<int, Member>
     */
    private function filtered(Request $request, Entity $entity)
    {
        return $entity->members()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('member_type_id'), fn ($q) => $q->where('member_type_id', $request->string('member_type_id')))
            ->with('memberType')
            ->orderBy('last_name')->orderBy('first_name')
            ->get();
    }

    private function download(string $contents, string $mime, string $filename): StreamedResponse
    {
        return response()->streamDownload(fn () => print ($contents), $filename, ['Content-Type' => $mime]);
    }
}
