<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MagicLinkToken;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cubre el flujo de autenticación bajo /api/v1/auth, siempre a través del
 * TenantMiddleware (subdominio `demo`). En SQLite no hay schemas, así que tenant y
 * usuario conviven en una única BD; las migraciones de tenant se autocargan en testing.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    // URL completa: el host (no una cabecera Host suelta) es lo que determina
    // getHost() en las requests de test, y de ahí el subdominio del tenant.
    private const BASE = 'http://demo.gestioname.app';

    private const EMAIL = 'admin@demo.gestioname.app';

    private const PASSWORD = 'secret-password';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Tenant::create([
            'name' => 'Datarecover Demo',
            'subdomain' => 'demo',
            'plan' => 'professional',
            'status' => 'active',
        ]);

        (new RoleSeeder)->run();

        $this->user = User::create([
            'name' => 'Administrador',
            'email' => self::EMAIL,
            'password' => self::PASSWORD,   // cast `hashed`
        ]);
        $this->user->assignRole('admin');
    }

    /** Hace login y devuelve el token Sanctum en claro. */
    private function loginToken(): string
    {
        return $this->postJson(self::BASE.'/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ])->json('data.token');
    }

    public function test_login_correcto_devuelve_token_y_roles(): void
    {
        $this->postJson(self::BASE.'/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'email', 'roles']],
            ])
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', self::EMAIL)
            ->assertJsonPath('data.user.roles', ['admin']);
    }

    public function test_login_con_contrasena_incorrecta_devuelve_422(): void
    {
        $this->postJson(self::BASE.'/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => 'mal',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_login_valida_campos_obligatorios(): void
    {
        $this->postJson(self::BASE.'/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_requiere_autenticacion(): void
    {
        $this->getJson(self::BASE.'/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_devuelve_el_usuario_autenticado(): void
    {
        $token = $this->loginToken();

        $this->withToken($token)->getJson(self::BASE.'/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', self::EMAIL)
            ->assertJsonPath('data.roles', ['admin']);
    }

    public function test_logout_revoca_el_token_actual(): void
    {
        $token = $this->loginToken();

        $this->withToken($token)->postJson(self::BASE.'/api/v1/auth/logout')->assertOk();

        // La app de test persiste entre requests y el guard cachea el usuario resuelto;
        // lo olvidamos para que la siguiente request re-resuelva el token desde BD
        // (en producción cada request es un proceso nuevo).
        $this->app['auth']->forgetGuards();

        // El token ya no es válido.
        $this->withToken($token)->getJson(self::BASE.'/api/v1/auth/me')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_refresh_rota_el_token(): void
    {
        $oldToken = $this->loginToken();

        $newToken = $this->withToken($oldToken)->postJson(self::BASE.'/api/v1/auth/refresh')
            ->assertOk()
            ->json('data.token');

        $this->assertNotSame($oldToken, $newToken);
        $this->app['auth']->forgetGuards();

        // El token antiguo queda revocado y el nuevo funciona.
        $this->withToken($oldToken)->getJson(self::BASE.'/api/v1/auth/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($newToken)->getJson(self::BASE.'/api/v1/auth/me')->assertOk();
    }

    public function test_magic_link_envia_notificacion_y_crea_token(): void
    {
        Notification::fake();

        $this->postJson(self::BASE.'/api/v1/auth/magic-link', ['email' => self::EMAIL])->assertOk();

        $this->assertDatabaseHas('magic_link_tokens', ['email' => self::EMAIL]);
        Notification::assertSentTo($this->user, MagicLinkNotification::class);
    }

    public function test_magic_link_no_revela_si_el_email_existe(): void
    {
        Notification::fake();

        $this->postJson(self::BASE.'/api/v1/auth/magic-link', ['email' => 'desconocido@demo.gestioname.app'])
            ->assertOk();

        $this->assertDatabaseCount('magic_link_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_magic_link_verify_valido_devuelve_token(): void
    {
        $plain = 'token-en-claro-de-prueba';
        MagicLinkToken::create([
            'email' => self::EMAIL,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson(self::BASE.'/api/v1/auth/magic-link/verify', ['token' => $plain])
            ->assertOk()
            ->assertJsonPath('data.user.email', self::EMAIL)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertNotNull(MagicLinkToken::first()->used_at);
    }

    public function test_magic_link_verify_token_invalido_devuelve_422(): void
    {
        $this->postJson(self::BASE.'/api/v1/auth/magic-link/verify', ['token' => 'no-existe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_magic_link_verify_es_de_un_solo_uso(): void
    {
        $plain = 'token-un-solo-uso';
        MagicLinkToken::create([
            'email' => self::EMAIL,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson(self::BASE.'/api/v1/auth/magic-link/verify', ['token' => $plain])->assertOk();
        // Segundo intento con el mismo token: rechazado.
        $this->postJson(self::BASE.'/api/v1/auth/magic-link/verify', ['token' => $plain])->assertStatus(422);
    }

    public function test_magic_link_verify_token_caducado_devuelve_422(): void
    {
        $plain = 'token-caducado';
        MagicLinkToken::create([
            'email' => self::EMAIL,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson(self::BASE.'/api/v1/auth/magic-link/verify', ['token' => $plain])->assertStatus(422);
    }

    public function test_login_esta_limitado_a_5_intentos_por_minuto(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::BASE.'/api/v1/auth/login', [
                'email' => self::EMAIL,
                'password' => 'mal',
            ])->assertStatus(422);
        }

        // El 6º intento queda bloqueado por el rate limiter.
        $this->postJson(self::BASE.'/api/v1/auth/login', [
            'email' => self::EMAIL,
            'password' => 'mal',
        ])->assertStatus(429);
    }
}
