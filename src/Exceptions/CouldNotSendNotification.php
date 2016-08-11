<?php

namespace NotificationChannels\Telegram\Exceptions;

use GuzzleHttp\Exception\ClientException;

class CouldNotSendNotification extends \Exception
{
    /**
     * Thrown when there's a bad request and an error is responded.
     *
     * @param ClientException $e
     *
     * @return static
     */
    public static function telegramRespondedWithAnError(ClientException $e)
    {
        $statusCode = $e->getResponse()->getStatusCode();

        if ($result = json_decode($e->getResponse()->getBody())) {
            if (isset($result->description)) {
                return new static(sprintf(
                    'Telegram responded with an error (%d): %s',
                    $statusCode,
                    $result->description
                ));
            }
        }

        return new static('Telegram responded with an error ('.$statusCode.').');
    }

    /**
     * Thrown when there's no bot token provided.
     *
     * @param $message
     *
     * @return static
     */
    public static function telegramBotTokenNotProvided($message)
    {
        return new static($message);
    }

    /**
     * Thrown when we're unable to communicate with Telegram.
     *
     * @return static
     */
    public static function serviceCommunicationError()
    {
        return new static("The communication with Telegram failed.");
    }
}
