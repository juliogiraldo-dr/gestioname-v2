<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

class EmployeeProfileTest extends TenantTestCase
{
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
        $this->employee = Employee::create(['company_id' => $company->id, 'first_name' => 'Ana', 'last_name' => 'García']);
    }

    public function test_crud_formacion(): void
    {
        $this->postJson($this->url("/employees/{$this->employee->id}/qualifications"), [
            'type' => 'curso', 'name' => 'Prevención de riesgos', 'date_obtained' => '2026-01-10',
        ])->assertCreated();

        $id = $this->getJson($this->url("/employees/{$this->employee->id}/qualifications"))
            ->assertOk()->assertJsonCount(1, 'data')->json('data.0.id');

        $this->deleteJson($this->url("/qualifications/{$id}"))->assertOk();
        $this->getJson($this->url("/employees/{$this->employee->id}/qualifications"))->assertJsonCount(0, 'data');
    }

    public function test_crud_materiales(): void
    {
        $id = $this->postJson($this->url("/employees/{$this->employee->id}/materials"), [
            'name' => 'Portátil', 'status' => 'entregado',
        ])->assertCreated()->json('data.id');

        $this->putJson($this->url("/materials/{$id}"), ['name' => 'Portátil', 'status' => 'devuelto'])
            ->assertOk()->assertJsonPath('data.status', 'devuelto');
    }

    public function test_registra_comportamiento(): void
    {
        $this->postJson($this->url("/employees/{$this->employee->id}/behavior"), [
            'type' => 'felicitacion', 'date' => '2026-02-01', 'description' => 'Buen trabajo',
        ])->assertCreated();

        $this->getJson($this->url("/employees/{$this->employee->id}/behavior"))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_sube_y_borra_documento(): void
    {
        Storage::fake('local');

        $id = $this->postJson($this->url("/employees/{$this->employee->id}/documents"), [
            'file' => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
            'name' => 'Contrato',
        ])->assertCreated()->json('data.id');

        $path = \App\Models\EmployeeDocument::find($id)->file_path;
        Storage::assertExists($path);

        $this->deleteJson($this->url("/documents/{$id}"))->assertOk();
        Storage::assertMissing($path);
    }
}
