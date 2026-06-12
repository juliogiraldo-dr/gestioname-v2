<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberPayment\StoreMemberPaymentRequest;
use App\Http\Requests\MemberPayment\UpdateMemberPaymentRequest;
use App\Http\Resources\MemberPaymentResource;
use App\Models\Entity;
use App\Models\Member;
use App\Models\MemberPayment;
use App\Services\Socios\MemberPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberPaymentController extends Controller
{
    public function __construct(private readonly MemberPaymentService $service) {}

    public function index(Member $member): AnonymousResourceCollection
    {
        return MemberPaymentResource::collection(
            $member->payments()->orderByDesc('year')->get()
        );
    }

    /** Pagos de toda una entidad (para Tesorería), con filtros por año y estado. */
    public function byEntity(Request $request, Entity $entity): AnonymousResourceCollection
    {
        $payments = MemberPayment::query()
            ->where('entity_id', $entity->id)
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with('member')
            ->orderByDesc('year')
            ->get();

        return MemberPaymentResource::collection($payments);
    }

    public function store(StoreMemberPaymentRequest $request, Member $member): JsonResponse
    {
        $payment = $this->service->create($member, $request->validated(), $request->user()?->id);

        return MemberPaymentResource::make($payment)->response()->setStatusCode(201);
    }

    public function update(UpdateMemberPaymentRequest $request, MemberPayment $memberPayment): MemberPaymentResource
    {
        return MemberPaymentResource::make($this->service->update($memberPayment, $request->validated()));
    }

    public function destroy(MemberPayment $memberPayment): JsonResponse
    {
        $this->service->delete($memberPayment);

        return response()->json(['message' => 'Pago eliminado correctamente.']);
    }
}
