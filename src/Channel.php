<?php

namespace NotificationChannels\Telegram;

use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Events\MessageWasSent;
use NotificationChannels\Telegram\Events\SendingMessage;
use Illuminate\Notifications\Notification;
use GuzzleHttp\Exception\ClientException;

class Channel
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
     * @return bool|void
     * @throws CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toTelegram($notifiable);

        if (is_string($message)) {
            $message = new Message($message);
        }

        if (!$chatId = $this->chatId($message, $notifiable)) {
            return;
        }

        $shouldSendMessage = event(new SendingMessage($notifiable, $notification), [], true) !== false;

        if (!$shouldSendMessage) {
            return;
        }

        $params = array_merge([
            'chat_id' => $chatId,
            'text' => trim($message->content),
            'parse_mode' => 'Markdown',
            'reply_markup' => $this->getReplyMarkup($message),
        ], $message->options);

        try {
            $response = $this->telegram->sendMessage($params);

            if ($response->getStatusCode() === 200) {
                event(new MessageWasSent($notifiable, $notification));

                return true;
            }

            $responseBody = $response->getBody();
        } catch (ClientException $e) {
            $responseBody = $e->getResponse()->getBody();
        }

        throw CouldNotSendNotification::telegramRespondedWithAnError($responseBody);
    }

    /**
     * @param Message         $message
     * @param                 $notifiable
     *
     * @return mixed
     */
    protected function chatId(Message $message, $notifiable)
    {
        return $message->chatId ?: $notifiable->routeNotificationFor('telegram') ?: null;
    }

    /**
     * Get Reply Markup (Inline Keyboard).
     *
     * @param Message $message
     *
     * @return $this|void
     */
    protected function getReplyMarkup(Message $message)
    {
        if (!$message->actionText) {
            return;
        }

        return (new Telegram)
            ->buttons([
                'text' => $message->actionText,
                'url' => $message->actionUrl,
            ])
            ->getKeyboardMarkup();
    }
}
