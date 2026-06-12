<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SuperAdminAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = SuperAdminAuditLog::query()
            ->with('tenant:id,name,subdomain')
            ->when($request->filled('tenant_id'), fn ($q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => collect($logs->items())->map(fn (SuperAdminAuditLog $l) => [
                'id' => $l->id,
                'action' => $l->action,
                'actor' => $l->details['actor_email'] ?? null,
                'tenant' => $l->tenant?->name,
                'tenant_id' => $l->tenant_id,
                'details' => $l->details,
                'ip' => $l->ip,
                'created_at' => $l->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
