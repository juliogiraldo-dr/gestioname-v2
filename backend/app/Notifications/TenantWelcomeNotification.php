<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email de bienvenida a un tenant recién creado: enlace de acceso (magic link) y
 * resumen del plan contratado.
 */
class TenantWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $orgName,
        private readonly string $accessUrl,
        private readonly string $planName,
        private readonly int $trialDays,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trialLine = $this->trialDays > 0
            ? "Tu plan {$this->planName} incluye {$this->trialDays} días de prueba gratuita."
            : "Estás en el plan {$this->planName}.";

        return (new MailMessage)
            ->subject("Bienvenido a Gestioname, {$this->orgName}")
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => "¡Bienvenido, {$this->orgName}!",
                'lines' => [
                    'Tu cuenta ya está lista.',
                    $trialLine,
                    'Pulsa el botón para acceder; no necesitas contraseña.',
                ],
                'actionText' => 'Acceder a Gestioname',
                'actionUrl' => $this->accessUrl,
                'outro' => 'Este enlace caduca en 24 horas. Si no has creado esta cuenta, ignora este mensaje.',
            ]);
    }
}
