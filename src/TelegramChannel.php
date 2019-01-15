<?php

namespace NotificationChannels\Telegram;

use Illuminate\Notifications\Notification;
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
        $message = $notification->toTelegram($notifiable);

        if (is_string($message)) {
            $message = TelegramMessage::create($message);
        }

        $this->telegram->setToken($message->token? $message->token: $this->telegram->getToken());
        if ($message->toNotGiven()) {
            if (!$to = $notifiable->routeNotificationFor('telegram')) {
                throw CouldNotSendNotification::chatIdNotProvided();
            }

            $message->to($to);
        }

        if(isset($message->payload['text']) && $message->payload['text'])
        {
            $params = $message->toArray();
            $this->telegram->sendMessage($params);
        }
        else
        {
            if(isset($message->payload['file']))
            {
                $params = $message->toMultipart();
                $this->telegram->sendFile($params, $message->type, true);
            }
            else
            {
                $params = $message->toArray();
                $this->telegram->sendFile($params, $message->type);
            }
        }
    }
}
