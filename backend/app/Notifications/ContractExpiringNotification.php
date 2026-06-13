<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al admin de que el contrato de un empleado vence pronto (30 o 7 días).
 */
class ContractExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $employeeName,
        private readonly int $daysLeft,
        private readonly string $endDate,
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
            ->subject("Contrato de {$this->employeeName} vence en {$this->daysLeft} días")
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => 'Vencimiento de contrato',
                'lines' => [
                    "El contrato de {$this->employeeName} vence el {$this->endDate} (en {$this->daysLeft} días).",
                    'Revisa si procede renovarlo o finalizarlo.',
                ],
                'actionText' => 'Ver empleados',
                'actionUrl' => rtrim((string) config('app.frontend_url'), '/').'/admin/empleados',
            ]);
    }
}
