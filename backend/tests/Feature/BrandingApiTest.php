<?php

declare(strict_types=1);

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class BrandingApiTest extends TenantTestCase
{
    public function test_branding_por_defecto_es_gestioname(): void
    {
        $this->getJson($this->url('/branding'))
            ->assertOk()
            ->assertJsonPath('data.app_name', 'Gestioname')
            ->assertJsonPath('data.primary_color', '#0F2756')
            ->assertJsonPath('data.custom_domain', null);
    }

    public function test_configura_branding_y_dominio_propio(): void
    {
        $this->putJson($this->url('/branding'), [
            'app_name' => 'Mi App', 'primary_color' => '#123456', 'custom_domain' => 'app.cliente.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.app_name', 'Mi App')
            ->assertJsonPath('data.primary_color', '#123456')
            ->assertJsonPath('data.custom_domain', 'app.cliente.com');

        $this->getJson($this->url('/branding'))->assertJsonPath('data.app_name', 'Mi App');
        $this->assertDatabaseHas('tenants', ['subdomain' => 'demo', 'custom_domain' => 'app.cliente.com']);
    }

    public function test_color_invalido_se_rechaza(): void
    {
        $this->putJson($this->url('/branding'), ['primary_color' => 'rojo'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('primary_color');
    }

    public function test_solo_admin_edita_branding(): void
    {
        Sanctum::actingAs($this->makeUser('employee'));

        $this->putJson($this->url('/branding'), ['app_name' => 'X'])->assertForbidden();
    }
}
