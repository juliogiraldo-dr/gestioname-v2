<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Database\Seeders\ChartOfAccountsSeeder;
use Tests\TenantTestCase;

class AccountingTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new ChartOfAccountsSeeder)->run();
    }

    private function acc(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function entry(array $lines, string $date = '2026-03-01', string $desc = 'Asiento'): array
    {
        return ['date' => $date, 'description' => $desc, 'lines' => $lines];
    }

    public function test_plan_de_cuentas_sembrado(): void
    {
        $this->getJson($this->url('/accounting/accounts'))
            ->assertOk()
            ->assertJsonPath('data.0.code', '100');
        $this->assertSame(18, Account::count());
    }

    public function test_crea_asiento_cuadrado(): void
    {
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('572'), 'debit' => 100],
            ['account_id' => $this->acc('720'), 'credit' => 100],
        ]))->assertCreated()->assertJsonPath('data.description', 'Asiento');

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    public function test_rechaza_asiento_descuadrado(): void
    {
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('572'), 'debit' => 100],
            ['account_id' => $this->acc('720'), 'credit' => 90],
        ]))->assertStatus(422)->assertJsonPath('code', 'UNBALANCED_ENTRY');
    }

    public function test_no_admite_asientos_en_ejercicio_cerrado(): void
    {
        FiscalPeriod::create(['year' => 2026, 'status' => 'closed', 'closed_at' => now()]);

        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('572'), 'debit' => 50],
            ['account_id' => $this->acc('720'), 'credit' => 50],
        ]))->assertStatus(422)->assertJsonPath('code', 'PERIOD_CLOSED');
    }

    public function test_cuenta_de_resultados_y_balance(): void
    {
        // Ingreso: cobro de cuota 200 (banco debe, cuotas haber).
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('572'), 'debit' => 200],
            ['account_id' => $this->acc('720'), 'credit' => 200],
        ]))->assertCreated();
        // Gasto: compra 50 (gastos debe, banco haber).
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('600'), 'debit' => 50],
            ['account_id' => $this->acc('572'), 'credit' => 50],
        ]))->assertCreated();

        $income = $this->getJson($this->url('/accounting/income-statement?year=2026'))->assertOk();
        $this->assertEquals(200, $income->json('data.ingresos.total'));
        $this->assertEquals(50, $income->json('data.gastos.total'));
        $this->assertEquals(150, $income->json('data.resultado'));

        // Banco: 200 - 50 = 150 (activo). Resultado 150 va a patrimonio → cuadra.
        $bs = $this->getJson($this->url('/accounting/balance-sheet?year=2026'))->assertOk();
        $this->assertEquals(150, $bs->json('data.total_activo'));
        $this->assertEquals(150, $bs->json('data.total_pasivo_patrimonio'));
        $this->assertTrue($bs->json('data.balanced'));
    }

    public function test_exporta_suenlace_dat(): void
    {
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('572'), 'debit' => 200],
            ['account_id' => $this->acc('720'), 'credit' => 200],
        ]))->assertCreated();

        $res = $this->get($this->url('/accounting/export/suenlace?year=2026'))->assertOk();
        $content = $res->streamedContent();

        $this->assertStringStartsWith('00', $content);       // cabecera
        $this->assertStringContainsString("\r\n10", $content); // apunte
        $this->assertStringContainsString("\r\n99", $content); // pie
    }

    public function test_libro_mayor_de_una_cuenta(): void
    {
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('572'), 'debit' => 200],
            ['account_id' => $this->acc('720'), 'credit' => 200],
        ]))->assertCreated();
        $this->postJson($this->url('/accounting/journal-entries'), $this->entry([
            ['account_id' => $this->acc('600'), 'debit' => 50],
            ['account_id' => $this->acc('572'), 'credit' => 50],
        ], date: '2026-03-02'))->assertCreated();

        $ledger = $this->getJson($this->url('/accounting/ledger?account_id='.$this->acc('572').'&year=2026'))
            ->assertOk();
        $this->assertCount(2, $ledger->json('data'));
        $this->assertEquals(150, $ledger->json('data.1.balance'));
    }
}
