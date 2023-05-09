<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramPoll;

/**
 * Class TestPollNotification.
 */
class TestPollNotification extends Notification
{
    public function toTelegram($notifiable): TelegramPoll
    {
        return TelegramPoll::create()
            ->to(12345)
            ->question("Isn't Telegram Notification Channel Awesome?")
            ->choices(['Yes', 'No']);
    }
}
