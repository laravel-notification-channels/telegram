<?php

namespace NotificationChannels\Telegram\Contracts;

use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

interface TelegramSender
{
    /**
     * Send the given message.
     *
     * @return mixed
     *
     * @throws CouldNotSendNotification
     */
    public function send();
}
