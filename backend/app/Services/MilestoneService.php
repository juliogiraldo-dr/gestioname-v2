<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\AttendanceMilestone;
use App\Models\WorkCenter;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de hitos de fichaje.
 */
final class MilestoneService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AttendanceMilestone
    {
        $workCenterIds = $data['work_center_ids'] ?? null;
        unset($data['work_center_ids']);

        return DB::transaction(function () use ($data, $workCenterIds): AttendanceMilestone {
            $milestone = AttendanceMilestone::create($data);

            if ($workCenterIds !== null) {
                $this->assertSameCompany($milestone->company_id, $workCenterIds);
                $milestone->workCenters()->sync($workCenterIds);
            }

            return $milestone;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AttendanceMilestone $milestone, array $data): AttendanceMilestone
    {
        $hasWorkCenters = array_key_exists('work_center_ids', $data);
        $workCenterIds = $data['work_center_ids'] ?? [];
        unset($data['work_center_ids']);

        return DB::transaction(function () use ($milestone, $data, $hasWorkCenters, $workCenterIds): AttendanceMilestone {
            $milestone->update($data);

            if ($hasWorkCenters) {
                $this->assertSameCompany($milestone->company_id, $workCenterIds);
                $milestone->workCenters()->sync($workCenterIds);
            }

            return $milestone;
        });
    }

    public function delete(AttendanceMilestone $milestone): void
    {
        $milestone->delete();
    }

    /**
     * Todos los centros asignados deben pertenecer a la empresa del hito.
     *
     * @param  array<int, string>  $workCenterIds
     *
     * @throws BusinessException
     */
    private function assertSameCompany(string $companyId, array $workCenterIds): void
    {
        $ids = array_values(array_unique($workCenterIds));

        if ($ids === []) {
            return;
        }

        $valid = WorkCenter::whereIn('id', $ids)
            ->where('company_id', $companyId)
            ->count();

        if ($valid !== count($ids)) {
            throw new BusinessException(
                'Algún centro de trabajo no pertenece a la empresa del hito.',
                'WORK_CENTER_COMPANY_MISMATCH',
                422,
            );
        }
    }
}
