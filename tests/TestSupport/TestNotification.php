<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * Class TestNotification.
 */
class TestNotification extends Notification
{
    public function toTelegram($notifiable): TelegramMessage
    {
        return TelegramMessage::create('Laravel Notification Channels are awesome!')->to(12345);
    }
}
