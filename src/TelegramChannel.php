<?php

namespace NotificationChannels\Telegram;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

/**
 * Class TelegramChannel.
 */
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
     *
     * @return null|array
     *
     * @throws CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification): ?array
    {
        $message = $notification->toTelegram($notifiable);

        if (is_string($message)) {
            $message = TelegramMessage::create($message);
        }

        if ($message->toNotGiven()) {
            if (! $to = $notifiable->routeNotificationFor('telegram')) {
                throw CouldNotSendNotification::chatIdNotProvided();
            }

            $message->to($to);
        }

        $params = $message->toArray();

        if ($message instanceof TelegramMessage) {
            $response = $this->telegram->sendMessage($params);
        } elseif ($message instanceof TelegramLocation) {
            $response = $this->telegram->sendLocation($params);
        } elseif ($message instanceof TelegramFile) {
            $response = $this->telegram->sendFile($params, $message->type, $message->hasFile());
        }

        return json_decode($response->getBody()->getContents());
    }
}
