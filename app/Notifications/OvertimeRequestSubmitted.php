<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OvertimeRequestSubmitted extends Notification
{
    use Queueable;

    protected $employee_name;

    public function __construct($employee_name)
    {
        $this->employee_name = $employee_name;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "{$this->employee_name} applies for overtime and needs approval.",
            'url' => '/overtime/management'
        ];
    }
}
