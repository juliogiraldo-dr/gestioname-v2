<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recibo de nómina (PDF) de un empleado para un mes/año.
 *
 * @property string $id
 * @property string $employee_id
 * @property int $month
 * @property int $year
 * @property string $file_path
 * @property string $original_name
 */
class Payslip extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'employee_id', 'month', 'year', 'file_path', 'original_name', 'uploaded_by', 'notified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'notified_at' => 'datetime',
        ];
    }

    /** Etiqueta «mes/año» en español para emails y registros. */
    public function periodLabel(): string
    {
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        return ($meses[$this->month] ?? (string) $this->month).' de '.$this->year;
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
