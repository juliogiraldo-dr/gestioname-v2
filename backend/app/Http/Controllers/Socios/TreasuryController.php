<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Services\Socios\TreasuryService;
use Illuminate\Http\JsonResponse;

class TreasuryController extends Controller
{
    public function __construct(private readonly TreasuryService $service) {}

    /** Tesorería del ejercicio indicado (o el activo de la entidad, o el año actual). */
    public function show(Entity $entity, ?int $year = null): JsonResponse
    {
        $year ??= $entity->fiscal_year ?? (int) now()->year;

        return response()->json(['data' => $this->service->compute($entity, $year)]);
    }
}
