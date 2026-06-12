<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Health check de infraestructura: base de datos, Redis y cola. GET /health.
 */
class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'database' => $this->safe(fn () => DB::select('select 1')),
            'redis' => $this->safe(fn () => Redis::connection()->ping()),
            'queue' => $this->safe(fn () => Queue::size()),
        ];

        $ok = collect($checks)->every(fn (array $c) => $c['ok']);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ], $ok ? 200 : 503);
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    private function safe(callable $fn): array
    {
        try {
            $fn();

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
