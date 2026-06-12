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
        $agreementIds = $this->extractAgreementIds($data);

        $workCenter = $company->workCenters()->create($data);

        if ($agreementIds !== null) {
            $workCenter->agreements()->sync($agreementIds);
        }

        return $workCenter->load('agreements');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkCenter $workCenter, array $data): WorkCenter
    {
        $agreementIds = $this->extractAgreementIds($data);

        $workCenter->update($data);

        if ($agreementIds !== null) {
            $workCenter->agreements()->sync($agreementIds);
        }

        return $workCenter->load('agreements');
    }

    /**
     * Extrae los IDs de convenio del payload (no son columna del centro).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>|null
     */
    private function extractAgreementIds(array &$data): ?array
    {
        if (! array_key_exists('agreement_ids', $data)) {
            return null;
        }
        $ids = $data['agreement_ids'] ?? [];
        unset($data['agreement_ids']);

        return $ids;
    }

    public function delete(WorkCenter $workCenter): void
    {
        // Los pivotes (milestone_work_centers, holiday_work_centers) se borran en cascada.
        $workCenter->delete();
    }
}
