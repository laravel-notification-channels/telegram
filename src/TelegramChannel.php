<?php

namespace NotificationChannels\Telegram;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Events\MessageWasSent;
use NotificationChannels\Telegram\Events\SendingMessage;

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
            $message = new TelegramMessage($message);
        }

        if (!$chatId = $this->chatId($message, $notifiable)) {
            return;
        }

        $shouldSendMessage = event(new SendingMessage($notifiable, $notification), [], true) !== false;

        if (!$shouldSendMessage) {
            return;
        }

        $params = array_merge([
            'chat_id'      => $chatId,
            'text'         => trim($message->content),
            'parse_mode'   => 'Markdown',
            'reply_markup' => $this->getReplyMarkup($message),
        ], $message->options);

        $this->telegram->sendMessage($params);

        event(new MessageWasSent($notifiable, $notification));
    }

    /**
     * @param TelegramMessage $message
     * @param         $notifiable
     *
     * @return mixed
     */
    protected function chatId(TelegramMessage $message, $notifiable)
    {
        return $message->chatId ?: $notifiable->routeNotificationFor('telegram') ?: null;
    }

    /**
     * Get Reply Markup (Inline Keyboard).
     *
     * @param TelegramMessage $message
     *
     * @return $this|void
     */
    protected function getReplyMarkup(TelegramMessage $message)
    {
        if (!$message->actionText) {
            return;
        }

        return (new Telegram())
            ->buttons([
                'text' => $message->actionText,
                'url'  => $message->actionUrl,
            ])
            ->getKeyboardMarkup();
    }
}
