<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification
{
    use Queueable;

    public $assignment;
    public $assignerName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;
        $this->assignerName = $assignment->assignedBy ? $assignment->assignedBy->name : 'Admin';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Simplified for now, can add 'mail' later if requested
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $date = $this->assignment->order && $this->assignment->order->pickup_time 
            ? \Carbon\Carbon::parse($this->assignment->order->pickup_time)->format('d M H:i')
            : 'Jadwal belum ditentukan';

        return [
            'assignment_id' => $this->assignment->id,
            'order_id' => $this->assignment->order_id,
            'message' => 'Tugas Baru: Jemput ' . $date . ' (Oleh ' . $this->assignerName . ')',
            'link' => route('assignments.my'), // Redirect to My Assignments
            'type' => 'new_assignment'
        ];
    }
}
