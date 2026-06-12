<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al empleado de que su nómina de un periodo ya está disponible en el portal.
 */
class PayslipAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $periodLabel,
        private readonly string $subdomain,
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
        $url = rtrim((string) config('app.frontend_url'), '/').'/portal/nominas';

        return (new MailMessage)
            ->subject("Tu nómina de {$this->periodLabel} está disponible")
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => 'Tu nómina está disponible',
                'lines' => [
                    "Ya puedes consultar y descargar tu nómina de {$this->periodLabel}.",
                    'Accede a tu portal del empleado, en la sección «Mis nóminas».',
                ],
                'actionText' => 'Ver mis nóminas',
                'actionUrl' => $url,
                'outro' => 'Si no esperabas este mensaje, contacta con tu departamento de RRHH.',
            ]);
    }
}
