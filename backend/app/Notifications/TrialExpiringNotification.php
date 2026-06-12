<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que el periodo de prueba caduca en pocos días.
 */
class TrialExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $orgName,
        private readonly int $daysLeft,
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
        return (new MailMessage)
            ->subject("Tu prueba de Gestioname caduca en {$this->daysLeft} días")
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => 'Tu periodo de prueba termina pronto',
                'lines' => [
                    "Hola, {$this->orgName}.",
                    "Tu prueba gratuita caduca en {$this->daysLeft} días.",
                    'Para seguir usando Gestioname sin interrupción, contacta con nosotros y elegimos el plan que mejor te encaja.',
                ],
                'actionText' => 'Contactar',
                'actionUrl' => 'mailto:info@datarecover.es',
                'outro' => 'Si ya has contratado un plan, ignora este mensaje.',
            ]);
    }
}
