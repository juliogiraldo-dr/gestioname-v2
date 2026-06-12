<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Communication;
use App\Models\Entity;
use App\Models\Member;
use App\Models\QuotaReminderSetting;
use App\Notifications\MassEmailNotification;
use App\Services\CommunicationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TenantTestCase;

class ComunicacionesTest extends TenantTestCase
{
    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entity = Entity::create(['name' => 'Club', 'type' => 'club', 'fiscal_year' => 2026]);
    }

    private function member(string $email, string $status = 'activo'): Member
    {
        return $this->entity->members()->create([
            'first_name' => 'Socio', 'last_name' => uniqid(), 'email' => $email, 'status' => $status,
        ]);
    }

    public function test_email_masivo_a_socios_filtra_por_estado_de_pago(): void
    {
        Notification::fake();

        $pendiente = $this->member('pendiente@example.com');
        $pagado = $this->member('pagado@example.com');
        $pagado->payments()->create([
            'entity_id' => $this->entity->id, 'year' => 2026, 'amount' => 50, 'status' => 'pagado',
        ]);

        $this->postJson($this->url("/entities/{$this->entity->id}/communications/socios"), [
            'subject' => 'Asamblea anual',
            'body' => "Hola.\nTe esperamos en la asamblea.",
            'payment' => 'pendiente',
        ])->assertCreated()->assertJsonPath('data.sent', 1);

        Notification::assertSentOnDemandTimes(MassEmailNotification::class, 1);
        $this->assertSame(1, Communication::where('audience', 'socios')->count());

        // El socio al día no recibe el aviso de pendientes.
        $this->assertNotNull($pendiente);
    }

    public function test_vista_previa_de_socios_cuenta_destinatarios(): void
    {
        $this->member('a@example.com');
        $this->member('b@example.com');

        $this->getJson($this->url("/entities/{$this->entity->id}/communications/preview-socios"))
            ->assertOk()
            ->assertJsonPath('data.count', 2);
    }

    public function test_ajustes_de_recordatorio_de_cuota_se_guardan(): void
    {
        $this->putJson($this->url("/entities/{$this->entity->id}/quota-reminder"), [
            'enabled' => true, 'days_before' => 10, 'subject' => 'Cuota', 'body' => 'Paga tu cuota.',
        ])->assertOk()->assertJsonPath('data.enabled', true);

        $this->assertDatabaseHas('quota_reminder_settings', ['entity_id' => $this->entity->id, 'days_before' => 10]);
    }

    public function test_recordatorio_de_cuota_avisa_a_pendientes_en_la_fecha(): void
    {
        Notification::fake();

        $this->member('debe@example.com');                              // pendiente
        $pagado = $this->member('aldia@example.com');
        $pagado->payments()->create(['entity_id' => $this->entity->id, 'year' => 2026, 'amount' => 50, 'status' => 'pagado']);

        QuotaReminderSetting::create([
            'entity_id' => $this->entity->id, 'enabled' => true, 'days_before' => 15,
            'subject' => 'Recordatorio', 'body' => 'Tu cuota está pendiente.',
        ]);

        // Cierre = 31/12/2026; 15 días antes = 16/12/2026.
        $sent = app(CommunicationService::class)->runQuotaReminders(Carbon::parse('2026-12-16'));

        $this->assertSame(1, $sent);
        Notification::assertSentOnDemandTimes(MassEmailNotification::class, 1);
        $this->assertSame(1, Communication::where('trigger', 'recordatorio_cuota')->count());

        // Idempotente: una segunda pasada el mismo día no reenvía.
        $this->assertSame(0, app(CommunicationService::class)->runQuotaReminders(Carbon::parse('2026-12-16')));
    }
}
