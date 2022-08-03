<?php

namespace NotificationChannels\Telegram;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
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
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * Channel constructor.
     */
    public function __construct(Telegram $telegram, Dispatcher $dispatcher)
    {
        $this->telegram = $telegram;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
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
            $to = $notifiable->routeNotificationFor('telegram', $notification)
                ?? $notifiable->routeNotificationFor(self::class, $notification);

            if (!$to) {
                return null;
            }

            $message->to($to);
        }

        if ($message->hasToken()) {
            $this->telegram->setToken($message->token);
        }

        try {
            $response = $message->send();

        } catch (CouldNotSendNotification $exception) {
            $this->dispatcher->dispatch(new NotificationFailed(
                $notifiable,
                $notification,
                'telegram',
                []
            ));

            throw $exception;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
