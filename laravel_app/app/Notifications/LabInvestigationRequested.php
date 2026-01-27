<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Consultation;

class LabInvestigationRequested extends Notification
{
    use Queueable;

    public $consultation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Consultation $consultation)
    {
        $this->consultation = $consultation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Lab investigation requested for ' . ($this->consultation->patient_name ?? 'Patient'),
            'url' => route('lab.dashboard', ['id' => $this->consultation->id]),
            'icon' => 'fa-flask',
            'type' => 'warning',
            'consultation_id' => $this->consultation->id
        ];
    }
}
