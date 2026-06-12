<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Holiday;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de festivos.
 */
final class HolidayService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Holiday
    {
        [$attributes, $workCenterIds] = $this->split($data);

        return DB::transaction(function () use ($attributes, $workCenterIds): Holiday {
            $holiday = Holiday::create($attributes);

            if ($workCenterIds !== null) {
                $holiday->workCenters()->sync($workCenterIds);
            }

            return $holiday;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Holiday $holiday, array $data): Holiday
    {
        [$attributes, $workCenterIds] = $this->split($data);

        return DB::transaction(function () use ($holiday, $attributes, $workCenterIds): Holiday {
            $holiday->update($attributes);

            if ($workCenterIds !== null) {
                $holiday->workCenters()->sync($workCenterIds);
            }

            return $holiday;
        });
    }

    public function delete(Holiday $holiday): void
    {
        $holiday->delete();
    }

    /**
     * Separa los atributos del festivo de la lista de centros, y normaliza los campos
     * mutuamente excluyentes según `repeatable`.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<int, string>|null}
     */
    private function split(array $data): array
    {
        $workCenterIds = array_key_exists('work_center_ids', $data) ? ($data['work_center_ids'] ?? []) : null;
        unset($data['work_center_ids']);

        // Coherencia: si es repetible no guarda fecha y viceversa.
        if (array_key_exists('repeatable', $data)) {
            if ($data['repeatable']) {
                $data['date'] = null;
            } else {
                $data['day_of_year'] = null;
            }
        }

        return [$data, $workCenterIds];
    }
}
