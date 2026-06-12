<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\TenantMiddleware;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pasa una request por el middleware y devuelve la respuesta de la closure final.
     * El host determina el subdominio (igual que en producción).
     */
    private function passThrough(string $host, ?string $tenantHeader = null): Response
    {
        $request = Request::create('http://'.$host.'/api/ping');
        if ($tenantHeader !== null) {
            $request->headers->set('X-Tenant-ID', $tenantHeader);
        }

        return (new TenantMiddleware)->handle(
            $request,
            fn (Request $req): Response => new Response('ok'),
        );
    }

    private function makeTenant(string $subdomain, string $status = 'active'): Tenant
    {
        return Tenant::create([
            'name' => 'Empresa '.$subdomain,
            'subdomain' => $subdomain,
            'plan' => 'free',
            'status' => $status,
        ]);
    }

    public function test_resuelve_tenant_por_subdominio_y_lo_expone_en_el_contenedor(): void
    {
        $tenant = $this->makeTenant('empresa1');

        $response = $this->passThrough('empresa1.gestioname.app');

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($tenant->id, app('tenant')->id);
    }

    public function test_expone_el_tenant_en_los_attributes_de_la_request(): void
    {
        $tenant = $this->makeTenant('empresa1');
        $request = Request::create('http://empresa1.gestioname.app/api/ping');

        $captured = null;
        (new TenantMiddleware)->handle($request, function (Request $req) use (&$captured): Response {
            $captured = $req->attributes->get('tenant');

            return new Response('ok');
        });

        $this->assertInstanceOf(Tenant::class, $captured);
        $this->assertSame($tenant->id, $captured->id);
    }

    public function test_ignora_el_puerto_en_desarrollo_local(): void
    {
        $tenant = $this->makeTenant('empresa1');

        $response = $this->passThrough('empresa1.localhost:8000');

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($tenant->id, app('tenant')->id);
    }

    public function test_admite_subdominios_con_guion(): void
    {
        $tenant = $this->makeTenant('mi-empresa');

        $response = $this->passThrough('mi-empresa.gestioname.app');

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($tenant->id, app('tenant')->id);
    }

    public function test_404_si_el_subdominio_no_corresponde_a_ningun_tenant(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->passThrough('inexistente.gestioname.app');
    }

    public function test_404_si_el_tenant_no_esta_activo(): void
    {
        $this->makeTenant('suspendida', status: 'suspended');

        $this->expectException(NotFoundHttpException::class);

        $this->passThrough('suspendida.gestioname.app');
    }

    public function test_404_para_subdominios_reservados(): void
    {
        // 'admin' es reservado aunque exista un registro homónimo: nunca es un tenant.
        $this->makeTenant('admin');

        $this->expectException(NotFoundHttpException::class);

        $this->passThrough('admin.gestioname.app');
    }

    public function test_404_para_host_sin_subdominio(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->passThrough('localhost');
    }

    public function test_resuelve_tenant_por_cabecera_x_tenant_id_sin_subdominio(): void
    {
        // Desarrollo local: http://localhost sin subdominio, tenant fijado por cabecera.
        $tenant = $this->makeTenant('demo');

        $response = $this->passThrough('localhost', tenantHeader: 'demo');

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($tenant->id, app('tenant')->id);
    }

    public function test_resuelve_tenant_por_dominio_propio(): void
    {
        // Marca blanca: el host no es un subdominio de gestioname.app sino el dominio del cliente.
        $tenant = Tenant::create([
            'name' => 'Cliente', 'subdomain' => 'cliente1', 'custom_domain' => 'app.suempresa.com',
            'plan' => 'free', 'status' => 'active',
        ]);

        $response = $this->passThrough('app.suempresa.com');

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($tenant->id, app('tenant')->id);
    }

    public function test_el_subdominio_tiene_prioridad_sobre_la_cabecera(): void
    {
        // En producción el subdominio real manda: la cabecera no puede suplantar a otro tenant.
        $real = $this->makeTenant('empresa1');
        $this->makeTenant('otra');

        $response = $this->passThrough('empresa1.gestioname.app', tenantHeader: 'otra');

        $this->assertSame('ok', $response->getContent());
        $this->assertSame($real->id, app('tenant')->id);
    }

    public function test_rechaza_identificadores_de_schema_no_validos(): void
    {
        // Defensa en profundidad: switchSchema bloquea cualquier identificador que no
        // sea una etiqueta DNS estricta antes de interpolarlo en `SET search_path`.
        $method = new ReflectionMethod(TenantMiddleware::class, 'switchSchema');
        $method->setAccessible(true);

        $this->expectException(NotFoundHttpException::class);

        $method->invoke(new TenantMiddleware, 'empresa1"; DROP SCHEMA public; --');
    }
}
