<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IP permitida para fichar de un empleado.
 *
 * @property string $id
 * @property string $employee_id
 * @property string $ip_address
 * @property string|null $description
 */
class EmployeeAllowedIp extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['employee_id', 'ip_address', 'description'];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
