<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

/**
 * Class TestNotificationNoChatId.
 */
class TestNotificationNoChatId extends Notification
{
    public function toTelegram($notifiable): TelegramMessage
    {
        return TelegramMessage::create();
    }
}
