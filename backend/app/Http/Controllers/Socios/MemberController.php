<?php

declare(strict_types=1);

namespace App\Http\Controllers\Socios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Entity;
use App\Models\Member;
use App\Services\Socios\MemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $service) {}

    public function index(Request $request, Entity $entity): AnonymousResourceCollection
    {
        $members = $entity->members()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('member_type_id'), fn ($q) => $q->where('member_type_id', $request->string('member_type_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('member_number', 'like', $term));
            })
            ->with('memberType')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return MemberResource::collection($members);
    }

    public function store(StoreMemberRequest $request, Entity $entity): JsonResponse
    {
        $member = $this->service->create($entity, $request->validated());

        return MemberResource::make($member->load('memberType'))->response()->setStatusCode(201);
    }

    public function show(Member $member): MemberResource
    {
        return MemberResource::make($member->load('memberType'));
    }

    public function update(UpdateMemberRequest $request, Member $member): MemberResource
    {
        return MemberResource::make($this->service->update($member, $request->validated())->load('memberType'));
    }

    public function destroy(Member $member): JsonResponse
    {
        $this->service->delete($member);

        return response()->json(['message' => 'Socio eliminado correctamente.']);
    }
}
