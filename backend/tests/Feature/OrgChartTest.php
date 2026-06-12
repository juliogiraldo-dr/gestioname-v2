<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OrgChartNode;
use App\Models\WorkCenter;
use Tests\TenantTestCase;

class OrgChartTest extends TenantTestCase
{
    private WorkCenter $center;

    private Employee $manager;

    private Employee $subordinate;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->center = $company->workCenters()->create(['name' => 'Sede Madrid']);
        $this->manager = Employee::create(['company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'Jefa']);
        $this->subordinate = Employee::create(['company_id' => $company->id, 'first_name' => 'Luis', 'last_name' => 'Becario']);
    }

    public function test_crea_nodos_y_devuelve_arbol(): void
    {
        $root = $this->postJson($this->url('/org-chart/nodes'), [
            'work_center_id' => $this->center->id,
            'employee_id' => $this->manager->id,
            'receives_notifications' => true,
        ])->assertCreated()->json('data.id');

        $this->postJson($this->url('/org-chart/nodes'), [
            'work_center_id' => $this->center->id,
            'employee_id' => $this->subordinate->id,
            'parent_id' => $root,
        ])->assertCreated();

        $this->getJson($this->url("/org-chart/{$this->center->id}"))
            ->assertOk()
            ->assertJsonCount(1, 'data')                 // un nodo raíz
            ->assertJsonCount(1, 'data.0.children')      // con un hijo
            ->assertJsonPath('data.0.receives_notifications', true);
    }

    public function test_actualiza_nodo(): void
    {
        $node = OrgChartNode::create([
            'work_center_id' => $this->center->id, 'employee_id' => $this->manager->id, 'sort_order' => 0,
        ]);

        $this->putJson($this->url("/org-chart/nodes/{$node->id}"), ['sort_order' => 5])
            ->assertOk()
            ->assertJsonPath('data.sort_order', 5);
    }

    public function test_toggle_notificaciones(): void
    {
        $node = OrgChartNode::create([
            'work_center_id' => $this->center->id, 'employee_id' => $this->manager->id,
        ]);

        $this->patchJson($this->url("/org-chart/nodes/{$node->id}/notifications"), ['receives_notifications' => true])
            ->assertOk()
            ->assertJsonPath('data.receives_notifications', true);
    }

    public function test_elimina_nodo(): void
    {
        $node = OrgChartNode::create([
            'work_center_id' => $this->center->id, 'employee_id' => $this->manager->id,
        ]);

        $this->deleteJson($this->url("/org-chart/nodes/{$node->id}"))->assertOk();
        $this->assertDatabaseMissing('org_chart_nodes', ['id' => $node->id]);
    }
}
