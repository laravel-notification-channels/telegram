<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMediaGroup;

class TestMediaGroupNotification extends Notification
{
    public function toTelegram($notifiable): TelegramMediaGroup
    {
        return TelegramMediaGroup::create()
            ->to(12345)
            ->photo('https://example.com/one.jpg', 'First image')
            ->photo('https://example.com/two.jpg');
    }
}
