<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramDice;

class TestDiceNotification extends Notification
{
    public function toTelegram($notifiable): TelegramDice
    {
        return TelegramDice::create()
            ->to(12345)
            ->emoji('🎯');
    }
}
