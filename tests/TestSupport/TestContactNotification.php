<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramContact;

/**
 * Class TestContactNotification.
 */
class TestContactNotification extends Notification
{
    /**
     * @param $notifiable
     * @return TelegramContact
     */
    public function toTelegram($notifiable): TelegramContact
    {
        return TelegramContact::create()
            ->to(12345)
            ->phoneNumber('123456789')
            ->firstName('John')
            ->lastName('Doe')
            ->vCard('vCard');
    }
}
