<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramVenue;

/**
 * Class TestVenueNotification.
 */
class TestVenueNotification extends Notification
{
    public function __construct(
        private float|string $latitude,
        private float|string $longitude,
        private string $title,
        private string $address
    ) {}

    public function toTelegram($notifiable): TelegramVenue
    {
        return TelegramVenue::create()
            ->to(12345)
            ->latitude($this->latitude)
            ->longitude($this->longitude)
            ->title($this->title)
            ->address($this->address)
            ->options(['foursquare_id' => '4sq12345']);
    }
}
