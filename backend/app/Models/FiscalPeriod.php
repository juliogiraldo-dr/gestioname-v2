<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ejercicio fiscal. Si está `closed` no se admiten asientos en ese año.
 *
 * @property int $id
 * @property int $year
 * @property string $status
 */
class FiscalPeriod extends Model
{
    /** @var list<string> */
    protected $fillable = ['year', 'entity_id', 'company_id', 'status', 'closed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['year' => 'integer', 'closed_at' => 'datetime'];
    }
}
