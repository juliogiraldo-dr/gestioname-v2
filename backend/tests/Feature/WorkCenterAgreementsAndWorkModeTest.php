<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agreement;
use App\Models\Attendance;
use App\Models\AttendanceMilestone;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\WorkCenter;
use Tests\TenantTestCase;

class WorkCenterAgreementsAndWorkModeTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    private function agreement(string $name): Agreement
    {
        return Agreement::create([
            'company_id' => $this->company->id, 'name' => $name,
            'annual_hours' => 1750, 'vacation_days' => 22, 'vacation_type' => 'laborables',
        ]);
    }

    public function test_centro_sincroniza_convenios_y_location_required(): void
    {
        $a1 = $this->agreement('Oficinas');
        $a2 = $this->agreement('Tienda');

        $res = $this->postJson($this->url("/companies/{$this->company->id}/work-centers"), [
            'name' => 'Sede', 'location_required' => true,
            'agreement_ids' => [$a1->id, $a2->id],
        ])->assertCreated()
            ->assertJsonPath('data.location_required', true);

        $centerId = $res->json('data.id');
        $this->assertCount(2, $res->json('data.agreement_ids'));
        $this->assertDatabaseHas('work_center_agreements', ['work_center_id' => $centerId, 'agreement_id' => $a1->id]);

        // Actualizar reemplaza el set de convenios.
        $this->putJson($this->url("/work-centers/{$centerId}"), ['agreement_ids' => [$a2->id]])
            ->assertOk()
            ->assertJsonCount(1, 'data.agreement_ids');
    }

    public function test_fichaje_guarda_modalidad_teletrabajo(): void
    {
        $employee = Employee::create([
            'company_id' => $this->company->id, 'first_name' => 'Juan', 'last_name' => 'G',
            'clock_code' => '12345678', 'active' => true,
        ]);
        $milestone = AttendanceMilestone::create([
            'company_id' => $this->company->id, 'name' => 'ENTRADA', 'type' => 'entrada', 'color' => '#000', 'active' => true,
        ]);

        $this->postJson($this->url('/attendance/clock'), [
            'clock_code' => '12345678',
            'milestone_id' => $milestone->id,
            'work_mode' => 'teletrabajo',
        ])->assertCreated()->assertJsonPath('data.work_mode', 'teletrabajo');

        $this->assertSame('teletrabajo', Attendance::first()->work_mode);
    }

    public function test_festivos_se_filtran_por_empresa(): void
    {
        $center = $this->company->workCenters()->create(['name' => 'Sede']);
        $otra = Company::create(['name' => 'Otra', 'cif' => 'B22222222']);
        $centroOtra = $otra->workCenters()->create(['name' => 'Sede 2']);

        // Festivo de mi empresa (vía su centro).
        $h1 = Holiday::create(['name' => 'Local Acme', 'type' => 'local', 'repeatable' => false, 'date' => '2026-09-15']);
        $h1->workCenters()->sync([$center->id]);
        // Festivo nacional (sin centros): debe aparecer para todas.
        Holiday::create(['name' => 'Nacional', 'type' => 'nacional', 'repeatable' => true, 'day_of_year' => '01-01']);
        // Festivo de otra empresa: NO debe aparecer.
        $h3 = Holiday::create(['name' => 'Local Otra', 'type' => 'local', 'repeatable' => false, 'date' => '2026-10-09']);
        $h3->workCenters()->sync([$centroOtra->id]);

        $names = collect($this->getJson($this->url("/holidays?company_id={$this->company->id}"))
            ->assertOk()->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Local Acme'));
        $this->assertTrue($names->contains('Nacional'));
        $this->assertFalse($names->contains('Local Otra'));
    }
}
