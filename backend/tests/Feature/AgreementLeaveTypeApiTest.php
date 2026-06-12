<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Company;
use Tests\TenantTestCase;

class AgreementLeaveTypeApiTest extends TenantTestCase
{
    private Agreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->agreement = Agreement::create([
            'company_id' => $company->id,
            'name' => 'Convenio Oficinas',
            'annual_hours' => 1780,
            'vacation_days' => 22,
            'vacation_type' => 'laborables',
        ]);
    }

    public function test_lista_tipos_del_convenio(): void
    {
        $this->agreement->leaveTypes()->create(['name' => 'Vacaciones', 'type' => 'ausencia', 'count_in' => 'dias']);
        $this->agreement->leaveTypes()->create(['name' => 'Horas extra', 'type' => 'presencia', 'count_in' => 'horas']);

        $this->getJson($this->url("/agreements/{$this->agreement->id}/leave-types"))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_crea_un_tipo(): void
    {
        $response = $this->postJson($this->url("/agreements/{$this->agreement->id}/leave-types"), [
            'name' => 'Médico',
            'type' => 'ausencia',
            'count_in' => 'horas',
            'requires_document' => true,
            'subtracts_vacation' => false,
            'max_hours' => 16.5,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Médico')
            ->assertJsonPath('data.requires_document', true);

        $this->assertDatabaseHas('agreement_leave_types', [
            'id' => $response->json('data.id'),
            'agreement_id' => $this->agreement->id,
            'count_in' => 'horas',
        ]);
    }

    public function test_valida_type_y_count_in(): void
    {
        $this->postJson($this->url("/agreements/{$this->agreement->id}/leave-types"), [
            'name' => 'Mal',
            'type' => 'otro',
            'count_in' => 'semanas',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'count_in']);
    }

    public function test_actualiza_un_tipo(): void
    {
        $leaveType = $this->agreement->leaveTypes()->create([
            'name' => 'Vacaciones', 'type' => 'ausencia', 'count_in' => 'dias',
        ]);

        $this->putJson($this->url("/leave-types/{$leaveType->id}"), ['max_days' => 22])
            ->assertOk()
            ->assertJsonPath('data.max_days', 22);
    }

    public function test_elimina_un_tipo(): void
    {
        $leaveType = $this->agreement->leaveTypes()->create([
            'name' => 'Vacaciones', 'type' => 'ausencia', 'count_in' => 'dias',
        ]);

        $this->deleteJson($this->url("/leave-types/{$leaveType->id}"))->assertOk();

        $this->assertDatabaseMissing('agreement_leave_types', ['id' => $leaveType->id]);
    }
}
