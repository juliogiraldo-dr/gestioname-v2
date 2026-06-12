<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso a coordinadores/administradores de una nueva solicitud de ausencia.
 */
class LeaveRequestSubmittedNotification extends Notification
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
        $employee = $this->leaveRequest->employee?->fullName() ?? 'Un empleado';

        return (new MailMessage)
            ->subject('Nueva solicitud de ausencia')
            ->line("{$employee} ha solicitado una ausencia.")
            ->line("Del {$this->leaveRequest->date_start->toDateString()} al {$this->leaveRequest->date_end->toDateString()}.")
            ->line('Revísala en el panel de Gestioname.');
    }
}
