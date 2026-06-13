<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ficha de empleado. `dni` e `iban` cifrados (LOPD/GDPR).
 *
 * @property string $id
 * @property string $company_id
 * @property string|null $work_center_id
 * @property string|null $agreement_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $clock_code
 * @property bool $active
 */
class Employee extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'company_id', 'work_center_id', 'agreement_id', 'user_id',
        'first_name', 'last_name', 'second_last_name', 'treatment', 'dni',
        'birth_date', 'birth_place', 'nationality',
        'clock_code', 'exempt_from_clock',
        'department', 'job_position', 'job_category', 'employment_status', 'hire_date', 'contract_end_date',
        'email_company', 'phone_company', 'mobile_company',
        'email_personal', 'phone_personal', 'address', 'postal_code', 'city', 'province',
        'iban', 'vehicle_plate', 'photo_path', 'notes', 'active',
    ];

    /** @var list<string> */
    protected $hidden = ['dni', 'iban'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dni' => 'encrypted',
            'iban' => 'encrypted',
            'birth_date' => 'date:Y-m-d',
            'hire_date' => 'date:Y-m-d',
            'contract_end_date' => 'date:Y-m-d',
            'exempt_from_clock' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name} {$this->second_last_name}");
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<WorkCenter, $this> */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /** @return BelongsTo<Agreement, $this> */
    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EmployeeAllowedIp, $this> */
    public function allowedIps(): HasMany
    {
        return $this->hasMany(EmployeeAllowedIp::class);
    }

    /** @return BelongsToMany<WorkCalendar, $this> */
    public function calendars(): BelongsToMany
    {
        return $this->belongsToMany(WorkCalendar::class, 'calendar_employees', 'employee_id', 'calendar_id');
    }

    /** @return HasMany<EmployeeQualification, $this> */
    public function qualifications(): HasMany
    {
        return $this->hasMany(EmployeeQualification::class);
    }

    /** @return HasMany<EmployeeMaterial, $this> */
    public function materials(): HasMany
    {
        return $this->hasMany(EmployeeMaterial::class);
    }

    /** @return HasMany<EmployeeBehaviorRecord, $this> */
    public function behaviorRecords(): HasMany
    {
        return $this->hasMany(EmployeeBehaviorRecord::class);
    }

    /** @return HasMany<EmployeeDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /** @return HasMany<Payslip, $this> */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /** Email de contacto del empleado (personal con preferencia, si no el de empresa). */
    public function contactEmail(): ?string
    {
        return $this->email_personal ?: $this->email_company;
    }
}
