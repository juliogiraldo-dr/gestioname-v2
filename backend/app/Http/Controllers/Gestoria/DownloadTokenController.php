<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gestoria;

use App\Http\Controllers\Controller;
use App\Models\DownloadToken;
use App\Models\Payslip;
use App\Services\DownloadTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Generación de enlaces públicos de descarga (un solo uso, 72 h) y registro de los mismos.
 * Para compartir una nómina con la gestoría externa sin darle acceso a la plataforma.
 */
class DownloadTokenController extends Controller
{
    public function __construct(private readonly DownloadTokenService $tokens) {}

    /** Registro de enlaces generados (quién, cuándo, descargado o no). */
    public function index(): JsonResponse
    {
        $tokens = DownloadToken::with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        $tokens->getCollection()->transform(fn (DownloadToken $t) => [
            'id' => $t->id,
            'kind' => $t->kind,
            'label' => $t->label,
            'filename' => $t->filename,
            'created_by' => $t->creator?->name,
            'created_at' => $t->created_at?->toIso8601String(),
            'expires_at' => $t->expires_at->toIso8601String(),
            'used_at' => $t->used_at?->toIso8601String(),
            'downloaded_ip' => $t->downloaded_ip,
            'status' => $t->used_at !== null ? 'descargado' : ($t->expires_at->isPast() ? 'caducado' : 'activo'),
        ]);

        return response()->json($tokens);
    }

    /** Genera un enlace de descarga para una nómina. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payslip_id' => ['required', 'uuid', 'exists:payslips,id'],
        ]);

        $payslip = Payslip::with('employee')->findOrFail($data['payslip_id']);

        $result = $this->tokens->create(
            filePath: $payslip->file_path,
            filename: $payslip->original_name,
            kind: 'nomina',
            label: "Nómina {$payslip->periodLabel()} · ".$payslip->employee->fullName(),
            createdBy: $request->user()?->id,
        );

        $url = $request->getSchemeAndHttpHost().'/api/v1/download/'.$result['token'];

        return response()->json(['data' => [
            'url' => $url,
            'expires_at' => $result['model']->expires_at->toIso8601String(),
        ]], 201);
    }
}
