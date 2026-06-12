<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class TlsCertificateTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Sanctum::actingAs($this->makeUser('super-admin'));
    }

    /**
     * Genera un par cert+key autofirmado para las pruebas.
     *
     * @return array{cert: string, key: string}
     */
    private function selfSigned(string $cn = '*.app.gestioname.es', int $days = 365): array
    {
        $pkey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => $cn], $pkey, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $pkey, $days, ['digest_alg' => 'sha256']);

        openssl_x509_export($x509, $certOut);
        openssl_pkey_export($pkey, $keyOut);

        return ['cert' => $certOut, 'key' => $keyOut];
    }

    public function test_sin_certificado_devuelve_null(): void
    {
        $this->getJson($this->url('/superadmin/tls-certificate'))
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_guarda_y_devuelve_info_del_certificado(): void
    {
        $pair = $this->selfSigned();

        $this->putJson($this->url('/superadmin/tls-certificate'), [
            'certificate' => $pair['cert'],
            'private_key' => $pair['key'],
        ])->assertOk()
            ->assertJsonPath('data.cn', '*.app.gestioname.es')
            ->assertJsonPath('data.domain', '*.app.gestioname.es');

        Storage::disk('local')->assertExists('tls/wildcard.crt');
        Storage::disk('local')->assertExists('tls/wildcard.key');

        $res = $this->getJson($this->url('/superadmin/tls-certificate'))->assertOk();
        $this->assertSame('*.app.gestioname.es', $res->json('data.cn'));
        $this->assertGreaterThan(360, $res->json('data.days_left'));
    }

    public function test_rechaza_certificado_invalido(): void
    {
        $this->putJson($this->url('/superadmin/tls-certificate'), [
            'certificate' => 'esto no es un certificado',
            'private_key' => '-----BEGIN PRIVATE KEY-----\nx\n-----END PRIVATE KEY-----',
        ])->assertStatus(422)->assertJsonValidationErrors('certificate');
    }

    public function test_rechaza_clave_que_no_corresponde(): void
    {
        $pair = $this->selfSigned();
        $otra = $this->selfSigned();

        $this->putJson($this->url('/superadmin/tls-certificate'), [
            'certificate' => $pair['cert'],
            'private_key' => $otra['key'],
        ])->assertStatus(422)->assertJsonValidationErrors('private_key');
    }

    public function test_requiere_rol_super_admin(): void
    {
        // $this->admin (rol admin) lo crea TenantTestCase en setUp.
        Sanctum::actingAs($this->admin);

        $this->getJson($this->url('/superadmin/tls-certificate'))->assertForbidden();
    }
}
