<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Consultation;

class PrescriptionGenerated extends Notification implements ShouldQueue
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
        $url = route('pharmacist.dashboard', ['id' => $this->consultation->id]);

        return (new MailMessage)
            ->subject('New Prescription Generated: ' . $patientName)
            ->greeting('Hello Pharmacist,')
            ->line('A new prescription has been generated for patient: **' . $patientName . '**.')
            ->action('View Prescription', $url)
            ->line('Please review the prescription details and fulfill the medication.')
            ->line('Thank you for your service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'New prescription generated for ' . ($this->consultation->patient_name ?? 'Patient'),
            'url' => route('pharmacist.dashboard', ['id' => $this->consultation->id]),
            'icon' => 'fa-file-prescription',
            'type' => 'info',
            'consultation_id' => $this->consultation->id
        ];
    }
}
