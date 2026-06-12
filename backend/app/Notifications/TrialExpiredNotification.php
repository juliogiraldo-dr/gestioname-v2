<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que el periodo de prueba ha caducado.
 */
class TrialExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $orgName) {}

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
            ->subject('Tu prueba de Gestioname ha caducado')
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => 'Tu periodo de prueba ha terminado',
                'lines' => [
                    "Hola, {$this->orgName}.",
                    'Tu prueba gratuita ha caducado. Tus datos siguen guardados.',
                    'Contáctanos para activar un plan y recuperar el acceso completo.',
                ],
                'actionText' => 'Contactar',
                'actionUrl' => 'mailto:info@datarecover.es',
            ]);
    }
}
