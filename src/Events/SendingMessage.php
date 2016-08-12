<?php

namespace NotificationChannels\Telegram\Events;

use Illuminate\Notifications\Notification;

class SendingMessage
{
    protected $notifiable;

    /** @var \Illuminate\Notifications\Notification */
    protected $notification;

    /**
     * @param $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     */
    public function __construct($notifiable, Notification $notification)
    {
        $this->notifiable = $notifiable;

        $this->notification = $notification;
    }
}
