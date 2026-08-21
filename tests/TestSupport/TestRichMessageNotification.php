<?php

namespace NotificationChannels\Telegram\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramRichMessage;

/**
 * Class TestRichMessageNotification.
 */
class TestRichMessageNotification extends Notification
{
    public function toTelegram($notifiable): TelegramRichMessage
    {
        return TelegramRichMessage::create()
            ->to(12345)
            ->heading('Invoice Paid', 1)
            ->paragraph('Thanks for your payment!')
            ->divider();
    }
}
