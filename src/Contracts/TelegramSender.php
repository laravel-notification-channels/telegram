<?php

namespace NotificationChannels\Telegram\Contracts;

use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

interface TelegramSender
{
    /**
     * Send the given message.
     *
     * @throws CouldNotSendNotification
     *
     * @return mixed
     */
    public function send();
}
