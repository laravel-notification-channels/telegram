<?php

namespace NotificationChannels\Telegram\Exceptions;

class CouldNotSendNotification extends \Exception
{
    public static function telegramRespondedWithAnError($response)
    {
        $response = json_decode($response);

        return new static($response->description, $response->error_code);
    }

    public static function telegramBotTokenNotProvided($message)
    {
        return new static($message);
    }
}
