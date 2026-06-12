<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Entity;
use Tests\TenantTestCase;

class TreasuryCalculationTest extends TenantTestCase
{
    public function test_calcula_saldo_de_tesoreria(): void
    {
        $entity = Entity::create([
            'name' => 'Club Deportivo', 'type' => 'club', 'opening_balance' => 1000, 'fiscal_year' => 2026,
        ]);
        $member = $entity->members()->create(['first_name' => 'Ana']);

        // Ingresos 2026: 50 cobrado + 30 parcial = 80 cobrado; 40 pendiente (no cuenta).
        $member->payments()->createMany([
            ['entity_id' => $entity->id, 'year' => 2026, 'amount' => 50, 'status' => 'pagado'],
            ['entity_id' => $entity->id, 'year' => 2026, 'amount' => 30, 'status' => 'parcial'],
            ['entity_id' => $entity->id, 'year' => 2026, 'amount' => 40, 'status' => 'pendiente'],
        ]);

        // Gastos 2026: 70; un gasto de 2025 que no debe contar.
        $entity->expenses()->createMany([
            ['amount' => 70, 'date' => '2026-03-01', 'description' => 'Material'],
            ['amount' => 999, 'date' => '2025-12-31', 'description' => 'Año anterior'],
        ]);

        $data = $this->getJson($this->url("/entities/{$entity->id}/treasury/2026"))
            ->assertOk()
            ->json('data');

        // Comparación laxa: un float entero como 1000.0 se serializa en JSON como 1000.
        $this->assertEqualsWithDelta(1000, $data['opening_balance'], 0.001);
        $this->assertEqualsWithDelta(80, $data['income'], 0.001);
        $this->assertEqualsWithDelta(40, $data['pending'], 0.001);
        $this->assertEqualsWithDelta(70, $data['expenses'], 0.001);
        $this->assertEqualsWithDelta(1010, $data['balance'], 0.001); // 1000 + 80 - 70
    }

    public function test_tesoreria_usa_ejercicio_activo_si_no_se_indica_año(): void
    {
        $entity = Entity::create([
            'name' => 'Club', 'type' => 'club', 'opening_balance' => 500, 'fiscal_year' => 2026,
        ]);

        $this->getJson($this->url("/entities/{$entity->id}/treasury"))
            ->assertOk()
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.balance', 500);
    }
}
