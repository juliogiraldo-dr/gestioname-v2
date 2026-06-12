<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Services\EmployeeImportService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TenantTestCase;

class EmployeeImportTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'cif' => 'B11111111']);
    }

    public function test_descarga_la_plantilla(): void
    {
        $this->get($this->url('/employees/template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_importa_empleados_desde_excel(): void
    {
        $path = $this->buildExcel([
            ['Juan', 'García', 'López', '12345678Z', '1990-05-01', 'juan@x.es', '600111222', 'IT', 'Dev', '2024-01-15'],
            ['Ana', 'Pérez', null, null, null, 'ana@x.es', null, 'RRHH', 'Manager', null],
            [null, null, null, null, null, null, null, null, null, null], // fila vacía: se ignora
            ['SinApellido', null, null, null, null, null, null, null, null, null], // error: falta last_name
        ]);

        $file = new UploadedFile(
            $path,
            'empleados.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->post($this->url('/employees/import'), [
            'company_id' => $this->company->id,
            'file' => $file,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonCount(1, 'data.errors');

        $this->assertDatabaseHas('employees', ['company_id' => $this->company->id, 'first_name' => 'Juan']);
        $this->assertDatabaseHas('employees', ['company_id' => $this->company->id, 'first_name' => 'Ana']);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function buildExcel(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(EmployeeImportService::COLUMNS, null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
