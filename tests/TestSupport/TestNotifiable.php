<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notifiable;

/**
 * Class TestNotifiable.
 */
class TestNotifiable
{
    use Notifiable;

    public function routeNotificationForTelegram(): bool
    {
        return false;
    }
}
