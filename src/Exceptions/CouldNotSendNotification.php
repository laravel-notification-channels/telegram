<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram\Exceptions;

use Exception;
use GuzzleHttp\Exception\ClientException;
use JsonException;

/**
 * Class CouldNotSendNotification.
 */
final class CouldNotSendNotification extends Exception
{
    /**
     * Thrown when there's a bad request and an error is responded.
     *
     * @throws JsonException
     */
    public static function telegramRespondedWithAnError(ClientException $exception): self
    {
        $response = $exception->getResponse();
        $statusCode = $response->getStatusCode();

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $description = is_array($result) && is_string($result['description'] ?? null)
            ? $result['description']
            : 'no description given';

        return new self("Telegram responded with an error `{$statusCode} - {$description}`", 0, $exception);
    }

    /**
     * Thrown when there's no bot token provided.
     */
    public static function telegramBotTokenNotProvided(string $message): self
    {
        return new self($message);
    }

    /**
     * Thrown when we're unable to communicate with Telegram.
     */
    public static function couldNotCommunicateWithTelegram(string $message): self
    {
        return new self("The communication with Telegram failed. `{$message}`");
    }

    /**
     * Thrown when the file cannot be opened.
     */
    public static function fileAccessFailed(string $file): self
    {
        return new self("Failed to open file: {$file}");
    }

    /**
     * Thrown when the file identifier is invalid (ID or URL).
     */
    public static function invalidFileIdentifier(string $file): self
    {
        return new self("Invalid file identifier: {$file}");
    }

    /**
     * Thrown when a rich message media identifier is invalid.
     */
    public static function invalidRichMessageMediaId(string $id): self
    {
        return new self("Invalid rich message media identifier: {$id}. It must be 1-64 characters long and contain only letters, digits, underscores and hyphens.");
    }
}
