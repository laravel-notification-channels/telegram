<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramLocation;

/**
 * Class TestLocationNotification.
 */
class TestLocationNotification extends Notification
{
    public function __construct(
        private float|string $latitude,
        private float|string $longitude
    ) {}

    public function toTelegram($notifiable): TelegramLocation
    {
        return TelegramLocation::create()
            ->to(12345)
            ->latitude($this->latitude)
            ->longitude($this->longitude)
            ->options(['horizontal_accuracy' => 100]);
    }
}
