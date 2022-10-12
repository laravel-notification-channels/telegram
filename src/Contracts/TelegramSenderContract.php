<?php

namespace NotificationChannels\Telegram\Contracts;

use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

interface TelegramSenderContract
{
    /**
     * Send the message.
     *
     * @return ResponseInterface|array|null
     *
     * @throws CouldNotSendNotification
     */
    public function send(): ResponseInterface|array|null;
}
