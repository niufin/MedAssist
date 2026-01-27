<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Consultation;

class MedicineFulfilled extends Notification implements ShouldQueue
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $patientName = $this->consultation->patient_name ?? 'Patient';
        $url = route('dashboard', ['id' => $this->consultation->id]);

        return (new MailMessage)
            ->subject('Medicine Fulfilled: ' . $patientName)
            ->greeting('Hello Doctor,')
            ->line('The prescribed medicines for patient **' . $patientName . '** have been marked as fulfilled by the pharmacist.')
            ->action('View Consultation', $url)
            ->line('You can review the status in the dashboard.')
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Medicines fulfilled for ' . ($this->consultation->patient_name ?? 'Patient'),
            'url' => route('dashboard', ['id' => $this->consultation->id]),
            'icon' => 'fa-pills',
            'type' => 'success',
            'consultation_id' => $this->consultation->id
        ];
    }
}
