<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNotification extends Notification
{
    use Queueable;
    public $obj;
    /**
     * Create a new notification instance.
     */
    public function __construct($obj)
    {
        $this->obj = $obj;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        $message = $this->obj->message;

        // Conditionally modify the subject if needed
        if (strpos($message, 'payment') !== false) {
            $subject = "Payment Reminder";
        } else {
            $subject = "General Notification";
        }
        return [
            'type' => 'admin',
            'subject' => $subject,
            'about' => $message,
            'message' => 'You have Notification from It-Linked',
        ];
    }
}
