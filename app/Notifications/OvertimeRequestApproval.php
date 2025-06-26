<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeRequestApproval extends Notification
{
     use Queueable;

    protected $admin_name, $status;

    public function __construct($admin_name, $status)
    {
        $this->admin_name = $admin_name;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "Your overtime request was {$this->status} by administrator {$this->admin_name}.",
            'url' => '/overtime'
        ];

    }
}
