<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\SuenlaceExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación del fichero `suenlace.dat` para a3asesor. Accesible por admin y gestoría.
 */
class SuenlaceController extends Controller
{
    public function __construct(private readonly SuenlaceExportService $service) {}

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'entity_id' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'uuid'],
        ]);

        $content = $this->service->build(
            (int) $data['year'],
            $data['entity_id'] ?? null,
            $data['company_id'] ?? null,
        );

        return response()->streamDownload(
            fn () => print ($content),
            "suenlace-{$data['year']}.dat",
            ['Content-Type' => 'application/octet-stream'],
        );
    }
}
