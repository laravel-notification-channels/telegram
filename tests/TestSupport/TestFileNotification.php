<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramFile;

/**
 * Class TestFileNotification.
 */
class TestFileNotification extends Notification
{
    public function toTelegram($notifiable): TelegramFile
    {
        return TelegramFile::create()
            ->to(12345)
            ->content('Some document')
            ->document('https://example.com/file.pdf');
    }
}
