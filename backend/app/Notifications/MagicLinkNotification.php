<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email con el enlace de acceso por magic link. El enlace apunta al frontend (Next.js),
 * que a su vez llama a POST /auth/magic-link/verify con el token.
 */
class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
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
        $url = sprintf(
            '%s/auth/magic-link?token=%s&tenant=%s',
            rtrim((string) config('app.frontend_url'), '/'),
            urlencode($this->token),
            urlencode($this->subdomain),
        );

        return (new MailMessage)
            ->subject('Tu enlace de acceso a Gestioname')
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => 'Tu enlace de acceso',
                'lines' => [
                    'Has solicitado acceder a Gestioname mediante un enlace.',
                    'Pulsa el botón para entrar; no necesitas contraseña.',
                ],
                'actionText' => 'Acceder',
                'actionUrl' => $url,
                'outro' => 'Este enlace caduca en 15 minutos y solo puede usarse una vez. Si no lo has solicitado, ignora este mensaje.',
            ]);
    }
}
