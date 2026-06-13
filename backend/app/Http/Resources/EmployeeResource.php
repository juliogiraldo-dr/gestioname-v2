<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'work_center_id' => $this->work_center_id,
            'agreement_id' => $this->agreement_id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'second_last_name' => $this->second_last_name,
            'full_name' => $this->fullName(),
            'treatment' => $this->treatment,
            'dni' => $this->dni,
            'birth_date' => $this->birth_date?->toDateString(),
            'nationality' => $this->nationality,
            'clock_code' => $this->clock_code,
            'exempt_from_clock' => $this->exempt_from_clock,
            'department' => $this->department,
            'job_position' => $this->job_position,
            'job_category' => $this->job_category,
            'employment_status' => $this->employment_status,
            'hire_date' => $this->hire_date?->toDateString(),
            'contract_end_date' => $this->contract_end_date?->toDateString(),
            'email_company' => $this->email_company,
            'phone_company' => $this->phone_company,
            'mobile_company' => $this->mobile_company,
            'email_personal' => $this->email_personal,
            'phone_personal' => $this->phone_personal,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'province' => $this->province,
            'iban' => $this->iban,
            'vehicle_plate' => $this->vehicle_plate,
            'photo_path' => $this->photo_path,
            'notes' => $this->notes,
            'active' => $this->active,
            'work_center' => WorkCenterResource::make($this->whenLoaded('workCenter')),
            'agreement' => AgreementResource::make($this->whenLoaded('agreement')),
            'allowed_ips' => $this->whenLoaded('allowedIps', fn () => $this->allowedIps->map(fn ($ip) => [
                'id' => $ip->id, 'ip_address' => $ip->ip_address, 'description' => $ip->description,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
