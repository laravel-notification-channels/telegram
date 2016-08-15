<?php

namespace NotificationChannels\Telegram;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Events\MessageWasSent;
use NotificationChannels\Telegram\Events\SendingMessage;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

class TelegramChannel
{
    /**
     * @var Telegram
     */
    protected $telegram;

    /**
     * Channel constructor.
     *
     * @param Telegram $telegram
     */
    public function __construct(Telegram $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Send the given notification.
     *
     * @param mixed        $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        if (!$this->shouldSendMessage($notifiable, $notification)) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        if (is_string($message)) {
            $message = TelegramMessage::create($message);
        }

        if ($message->toNotGiven()) {
            if (!$to = $notifiable->routeNotificationFor('telegram')) {
                throw CouldNotSendNotification::chatIdNotProvided();
            }

            $message->to($to);
        }

        $params = $message->toArray();

        $this->telegram->sendMessage($params);

        event(new MessageWasSent($notifiable, $notification));
    }

    /**
     * Check if we can send the notification.
     *
     * @param              $notifiable
     * @param Notification $notification
     *
     * @return bool
     */
    protected function shouldSendMessage($notifiable, Notification $notification)
    {
        return event(new SendingMessage($notifiable, $notification), [], true) !== false;
    }
}
