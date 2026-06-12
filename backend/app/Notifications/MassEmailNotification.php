<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email masivo genérico (comunicaciones a socios o empleados, y recordatorios de cuota).
 * Usa la plantilla branded `emails/action`. El cuerpo se divide en párrafos por saltos de línea.
 */
class MassEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $subjectLine,
        private readonly string $body,
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
        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', $this->body) ?: [],
            fn ($l) => trim($l) !== '',
        ));

        return (new MailMessage)
            ->subject($this->subjectLine)
            ->view('emails.action', [
                'appName' => 'Gestioname',
                'heading' => $this->subjectLine,
                'lines' => $lines,
            ]);
    }
}
