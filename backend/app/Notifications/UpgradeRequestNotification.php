<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Solicitud de cambio de plan enviada a Datarecover (info@datarecover.es).
 */
class UpgradeRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
        private readonly string $contactName,
        private readonly string $contactEmail,
        private readonly string $desiredPlan,
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
            ->subject("Solicitud de plan {$this->desiredPlan} — {$this->tenantName}")
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => 'Solicitud de cambio de plan',
                'lines' => [
                    "Tenant: {$this->tenantName}",
                    "Contacto: {$this->contactName} ({$this->contactEmail})",
                    "Plan solicitado: {$this->desiredPlan}",
                ],
                'outro' => 'Responde directamente al cliente para continuar la contratación.',
            ]);
    }
}
