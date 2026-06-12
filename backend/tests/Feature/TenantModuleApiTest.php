<?php

declare(strict_types=1);

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class TenantModuleApiTest extends TenantTestCase
{
    public function test_lista_el_catalogo_de_modulos(): void
    {
        $this->getJson($this->url('/tenant-modules'))
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.0.key', 'rrhh');
    }

    public function test_incluye_modulos_marca_blanca_y_multitenant(): void
    {
        $keys = collect($this->getJson($this->url('/tenant-modules'))->json('data'))->pluck('key');

        $this->assertTrue($keys->contains('white_label'));
        $this->assertTrue($keys->contains('multitenant'));
    }

    public function test_activa_y_desactiva_un_modulo(): void
    {
        $this->patchJson($this->url('/tenant-modules/nominas'), ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->assertDatabaseHas('tenant_modules', ['key' => 'nominas', 'enabled' => true]);

        $this->patchJson($this->url('/tenant-modules/nominas'), ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_modulo_desconocido_devuelve_404(): void
    {
        $this->patchJson($this->url('/tenant-modules/inexistente'), ['enabled' => true])
            ->assertNotFound();
    }

    public function test_gestor_rrhh_puede_leer_pero_no_activar(): void
    {
        Sanctum::actingAs($this->makeUser('rrhh-coordinator'));

        $this->getJson($this->url('/tenant-modules'))->assertOk();
        $this->patchJson($this->url('/tenant-modules/socios'), ['enabled' => false])->assertForbidden();
    }
}
