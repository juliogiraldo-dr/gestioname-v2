<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al empleado de la resolución (aprobada/rechazada) de su solicitud.
 */
class LeaveRequestReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leaveRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resolved = $this->leaveRequest->status === 'aprobada' ? 'APROBADA' : 'RECHAZADA';

        $mail = (new MailMessage)
            ->subject("Tu solicitud de ausencia ha sido {$resolved}")
            ->line("Tu solicitud del {$this->leaveRequest->date_start->toDateString()} ha sido {$resolved}.");

        if ($this->leaveRequest->review_note) {
            $mail->line("Nota: {$this->leaveRequest->review_note}");
        }

        return $mail;
    }
}
