<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram\Facades;

use Illuminate\Support\Facades\Facade;
use NotificationChannels\Telegram\Telegram as TelegramClient;
use Psr\Http\Message\ResponseInterface;

/**
 * @method static string|null getToken()
 * @method static TelegramClient setToken(string $token)
 * @method static string getApiBaseUri()
 * @method static TelegramClient setApiBaseUri(string $apiBaseUri)
 * @method static TelegramClient setHttpClient(\GuzzleHttp\Client $http)
 * @method static ResponseInterface sendMessage(array<string, mixed> $params)
 * @method static ResponseInterface sendFile(array<string, mixed> $params, string $type, bool $multipart = false)
 * @method static ResponseInterface sendRichMessage(array<string, mixed> $params)
 * @method static ResponseInterface sendRichMessageDraft(array<string, mixed> $params)
 * @method static ResponseInterface sendPoll(array<string, mixed> $params)
 * @method static ResponseInterface sendContact(array<string, mixed> $params)
 * @method static ResponseInterface getUpdates(array<string, mixed> $params)
 * @method static ResponseInterface sendLocation(array<string, mixed> $params)
 * @method static ResponseInterface sendVenue(array<string, mixed> $params)
 * @method static ResponseInterface sendDice(array<string, mixed> $params)
 * @method static ResponseInterface sendMediaGroup(array<string, mixed> $params, bool $multipart = false)
 * @method static ResponseInterface sendChatAction(array<string, mixed> $params)
 * @method static ResponseInterface editMessageText(array<string, mixed> $params)
 * @method static ResponseInterface editMessageCaption(array<string, mixed> $params)
 * @method static ResponseInterface editMessageMedia(array<string, mixed> $params, bool $multipart = false)
 * @method static ResponseInterface editMessageReplyMarkup(array<string, mixed> $params)
 * @method static ResponseInterface stopPoll(array<string, mixed> $params)
 * @method static ResponseInterface deleteMessage(array<string, mixed> $params)
 * @method static ResponseInterface deleteMessages(array<string, mixed> $params)
 *
 * @see TelegramClient
 */
class Telegram extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TelegramClient::class;
    }
}
