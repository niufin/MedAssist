<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\LabReport;

class LabReportUploaded extends Notification
{
    use Queueable;

    public $report;

    /**
     * Create a new notification instance.
     */
    public function __construct(LabReport $report)
    {
        $this->report = $report;
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
            'message' => 'Lab report uploaded for ' . ($this->report->consultation->patient_name ?? 'Patient'),
            'url' => route('dashboard', ['id' => $this->report->consultation_id]),
            'icon' => 'fa-file-medical',
            'type' => 'success',
            'consultation_id' => $this->report->consultation_id
        ];
    }
}
