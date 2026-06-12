<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\WorkCenter;

/**
 * Lógica de negocio de centros de trabajo.
 */
final class WorkCenterService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Company $company, array $data): WorkCenter
    {
        return $company->workCenters()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkCenter $workCenter, array $data): WorkCenter
    {
        $workCenter->update($data);

        return $workCenter;
    }

    public function delete(WorkCenter $workCenter): void
    {
        // Los pivotes (milestone_work_centers, holiday_work_centers) se borran en cascada.
        $workCenter->delete();
    }
}
