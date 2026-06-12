<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Company;
use Tests\TenantTestCase;

class AgreementApiTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function agreement(array $overrides = []): Agreement
    {
        return Agreement::create(array_merge([
            'company_id' => $this->company->id,
            'name' => 'Convenio Oficinas',
            'annual_hours' => 1780,
            'vacation_days' => 22,
            'vacation_type' => 'laborables',
        ], $overrides));
    }

    public function test_crea_convenio(): void
    {
        $response = $this->postJson($this->url('/agreements'), [
            'company_id' => $this->company->id,
            'name' => 'Convenio Oficinas',
            'annual_hours' => 1780.5,
            'vacation_days' => 22,
            'vacation_type' => 'laborables',
            'vacation_expiry' => '2027-01-31',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Convenio Oficinas')
            ->assertJsonPath('data.vacation_type', 'laborables');

        $this->assertDatabaseHas('agreements', [
            'id' => $response->json('data.id'),
            'company_id' => $this->company->id,
            'vacation_days' => 22,
        ]);
    }

    public function test_lista_filtrando_por_empresa(): void
    {
        $other = Company::create(['name' => 'Globex', 'cif' => 'B22222222']);
        $this->agreement();
        Agreement::create([
            'company_id' => $other->id,
            'name' => 'Otro',
            'annual_hours' => 1700,
            'vacation_days' => 30,
            'vacation_type' => 'naturales',
        ]);

        $this->getJson($this->url("/agreements?company_id={$this->company->id}"))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_muestra_convenio_con_tipos(): void
    {
        $agreement = $this->agreement();
        $agreement->leaveTypes()->create([
            'name' => 'Vacaciones',
            'type' => 'ausencia',
            'count_in' => 'dias',
            'subtracts_vacation' => true,
        ]);

        $this->getJson($this->url("/agreements/{$agreement->id}"))
            ->assertOk()
            ->assertJsonPath('data.id', $agreement->id)
            ->assertJsonCount(1, 'data.leave_types');
    }

    public function test_actualiza_convenio(): void
    {
        $agreement = $this->agreement();

        $this->putJson($this->url("/agreements/{$agreement->id}"), ['vacation_days' => 23])
            ->assertOk()
            ->assertJsonPath('data.vacation_days', 23);
    }

    public function test_elimina_convenio_y_sus_tipos_en_cascada(): void
    {
        $agreement = $this->agreement();
        $leaveType = $agreement->leaveTypes()->create([
            'name' => 'Vacaciones', 'type' => 'ausencia', 'count_in' => 'dias',
        ]);

        $this->deleteJson($this->url("/agreements/{$agreement->id}"))->assertOk();

        $this->assertDatabaseMissing('agreements', ['id' => $agreement->id]);
        $this->assertDatabaseMissing('agreement_leave_types', ['id' => $leaveType->id]);
    }

    public function test_valida_campos(): void
    {
        $this->postJson($this->url('/agreements'), [
            'company_id' => $this->company->id,
            'name' => 'Mal',
            'vacation_type' => 'inventado',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['annual_hours', 'vacation_days', 'vacation_type']);
    }
}
