<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\MemberType;
use Illuminate\Support\Facades\DB;
use Tests\TenantTestCase;

class MemberApiTest extends TenantTestCase
{
    private Entity $entity;

    private MemberType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::create(['name' => 'Peña El Casino', 'type' => 'pena', 'opening_balance' => 0]);
        $this->type = $this->entity->memberTypes()->create([
            'name' => 'Adulto', 'fee_amount' => 50, 'fee_periodicity' => 'anual',
        ]);
    }

    public function test_crea_socio_con_numero_autonumerado(): void
    {
        $first = $this->postJson($this->url("/entities/{$this->entity->id}/members"), [
            'first_name' => 'Ana', 'last_name' => 'López', 'member_type_id' => $this->type->id,
        ])->assertCreated()->json('data');

        $second = $this->postJson($this->url("/entities/{$this->entity->id}/members"), [
            'first_name' => 'Luis', 'last_name' => 'Pérez',
        ])->assertCreated()->json('data');

        $this->assertSame('1', $first['member_number']);
        $this->assertSame('2', $second['member_number']);
    }

    public function test_dni_se_cifra_en_base_de_datos(): void
    {
        $id = $this->postJson($this->url("/entities/{$this->entity->id}/members"), [
            'first_name' => 'Ana', 'dni' => '12345678Z',
        ])->assertCreated()->json('data.id');

        $raw = DB::table('members')->where('id', $id)->value('dni');

        $this->assertNotSame('12345678Z', $raw);
        $this->assertStringNotContainsString('12345678Z', (string) $raw);
    }

    public function test_lista_socios_filtra_por_estado(): void
    {
        $this->entity->members()->create(['first_name' => 'Activo', 'status' => 'activo']);
        $this->entity->members()->create(['first_name' => 'Baja', 'status' => 'baja_voluntaria']);

        $this->getJson($this->url("/entities/{$this->entity->id}/members?status=activo"))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Activo');
    }

    public function test_actualiza_socio(): void
    {
        $member = $this->entity->members()->create(['first_name' => 'Ana']);

        $this->putJson($this->url("/members/{$member->id}"), ['status' => 'honor'])
            ->assertOk()
            ->assertJsonPath('data.status', 'honor');
    }

    public function test_borra_socio(): void
    {
        $member = $this->entity->members()->create(['first_name' => 'Ana']);

        $this->deleteJson($this->url("/members/{$member->id}"))->assertOk();
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    public function test_no_se_puede_borrar_entidad_con_socios(): void
    {
        $this->entity->members()->create(['first_name' => 'Ana']);

        $this->deleteJson($this->url("/entities/{$this->entity->id}"))
            ->assertStatus(409)
            ->assertJsonPath('code', 'ENTITY_HAS_MEMBERS');
    }

    public function test_pago_usa_la_cuota_del_tipo_por_defecto(): void
    {
        $member = $this->entity->members()->create([
            'first_name' => 'Ana', 'member_type_id' => $this->type->id,
        ]);

        $this->postJson($this->url("/members/{$member->id}/payments"), [
            'year' => 2026, 'status' => 'pagado', 'payment_method' => 'efectivo',
        ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 50);
    }

    public function test_entidad_se_crea_con_categorias_de_gasto_por_defecto(): void
    {
        $id = $this->postJson($this->url('/entities'), ['name' => 'AMPA Colegio', 'type' => 'ampa'])
            ->assertCreated()->json('data.id');

        $this->assertGreaterThan(0, DB::table('expense_categories')->where('entity_id', $id)->count());
    }
}
