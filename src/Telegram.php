<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Str;
use JsonException;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Telegram.
 */
class Telegram
{
    /** Default Telegram Bot API Base URI.*/
    protected const string API_BASE_URI = 'https://api.telegram.org';

    /** Default request timeout in seconds. */
    public const int DEFAULT_TIMEOUT = 30;

    /** Default connection timeout in seconds. */
    public const int DEFAULT_CONNECT_TIMEOUT = 10;

    /** Maximum number of retries after a rate limited (429) response. */
    protected const int MAX_RATE_LIMIT_RETRIES = 1;

    /** Longest `retry_after` (in seconds) worth waiting for; anything above fails fast. */
    protected const int MAX_RETRY_AFTER = 60;

    protected string $apiBaseUri;

    protected HttpClient $http;

    public function __construct(
        protected ?string $token = null,
        ?HttpClient $http = null,
        ?string $apiBaseUri = null
    ) {
        $this->http = $http ?? new HttpClient([
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
        ]);

        $this->setApiBaseUri($apiBaseUri ?? static::API_BASE_URI);
    }

    /**
     * Token getter.
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Token setter.
     *
     * @return $this
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * API Base URI getter.
     */
    public function getApiBaseUri(): string
    {
        return $this->apiBaseUri;
    }

    /**
     * API Base URI setter.
     *
     * @return $this
     */
    public function setApiBaseUri(string $apiBaseUri): self
    {
        $this->apiBaseUri = rtrim($apiBaseUri, '/');

        return $this;
    }

    /**
     * Set HTTP Client.
     *
     * @return $this
     */
    public function setHttpClient(HttpClient $http): self
    {
        $this->http = $http;

        return $this;
    }

    /**
     * Send text message.
     *
     * <code>
     * $params = [
     *   'chat_id'                  => '',
     *   'text'                     => '',
     *   'parse_mode'               => '',
     *   'disable_web_page_preview' => '',
     *   'disable_notification'     => '',
     *   'reply_to_message_id'      => '',
     *   'reply_markup'             => '',
     * ];
     * </code>
     *
     * @see https://core.telegram.org/bots/api#sendmessage
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendMessage(array $params): ResponseInterface
    {
        return $this->sendRequest('sendMessage', $params);
    }

    /**
     * Send File as Image or Document.
     *
     * @param  array<string, mixed>|list<array{name: string, contents: mixed, filename?: string}>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendFile(array $params, string $type, bool $multipart = false): ResponseInterface
    {
        return $this->sendRequest('send'.Str::studly($type), $params, $multipart);
    }

    /**
     * Send a Rich Message.
     *
     * <code>
     * $params = [
     *   'chat_id'      => '',
     *   'rich_message' => '',
     *   'reply_markup' => '',
     * ];
     * </code>
     *
     * @see https://core.telegram.org/bots/api#sendrichmessage
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendRichMessage(array $params): ResponseInterface
    {
        return $this->sendRequest('sendRichMessage', $params);
    }

    /**
     * Send a Rich Message Draft.
     *
     * @see https://core.telegram.org/bots/api#sendrichmessagedraft
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendRichMessageDraft(array $params): ResponseInterface
    {
        return $this->sendRequest('sendRichMessageDraft', $params);
    }

    /**
     * Send a Poll.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendPoll(array $params): ResponseInterface
    {
        return $this->sendRequest('sendPoll', $params);
    }

    /**
     * Send a Contact.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendContact(array $params): ResponseInterface
    {
        return $this->sendRequest('sendContact', $params);
    }

    /**
     * Get updates.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function getUpdates(array $params): ResponseInterface
    {
        return $this->sendRequest('getUpdates', $params);
    }

    /**
     * Send a Location.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendLocation(array $params): ResponseInterface
    {
        return $this->sendRequest('sendLocation', $params);
    }

    /**
     * Send a Venue.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendVenue(array $params): ResponseInterface
    {
        return $this->sendRequest('sendVenue', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendDice(array $params): ResponseInterface
    {
        return $this->sendRequest('sendDice', $params);
    }

    /**
     * @param  array<string, mixed>|list<array{name: string, contents: mixed, filename?: string}>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendMediaGroup(array $params, bool $multipart = false): ResponseInterface
    {
        return $this->sendRequest('sendMediaGroup', $params, $multipart);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function sendChatAction(array $params): ResponseInterface
    {
        return $this->sendRequest('sendChatAction', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function editMessageText(array $params): ResponseInterface
    {
        return $this->sendRequest('editMessageText', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function editMessageCaption(array $params): ResponseInterface
    {
        return $this->sendRequest('editMessageCaption', $params);
    }

    /**
     * @param  array<string, mixed>|list<array{name: string, contents: mixed, filename?: string}>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function editMessageMedia(array $params, bool $multipart = false): ResponseInterface
    {
        return $this->sendRequest('editMessageMedia', $params, $multipart);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function editMessageReplyMarkup(array $params): ResponseInterface
    {
        return $this->sendRequest('editMessageReplyMarkup', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function stopPoll(array $params): ResponseInterface
    {
        return $this->sendRequest('stopPoll', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function deleteMessage(array $params): ResponseInterface
    {
        return $this->sendRequest('deleteMessage', $params);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws CouldNotSendNotification
     */
    public function deleteMessages(array $params): ResponseInterface
    {
        return $this->sendRequest('deleteMessages', $params);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public static function decodeResponse(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $decodedResponse */
        $decodedResponse = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $decodedResponse;
    }

    /**
     * Get HttpClient.
     */
    protected function httpClient(): HttpClient
    {
        return $this->http;
    }

    /**
     * Send an API request and return response.
     *
     * @param  array<string, mixed>|list<array{name: string, contents: mixed, filename?: string}>  $params
     *
     * @throws CouldNotSendNotification
     * @throws JsonException When a form param cannot be JSON encoded
     */
    protected function sendRequest(string $endpoint, array $params, bool $multipart = false): ResponseInterface
    {
        if (blank($this->token)) {
            throw CouldNotSendNotification::telegramBotTokenNotProvided('You must provide your telegram bot token to make any API requests.');
        }

        $apiUri = sprintf('%s/bot%s/%s', $this->apiBaseUri, $this->token, $endpoint);

        $options = $multipart
            ? ['multipart' => $this->multipartParams($params)]
            : ['form_params' => $this->formParams($params)];

        $attempts = 0;

        do {
            try {
                return $this->httpClient()->post($apiUri, $options);
            } catch (BadResponseException $exception) {
                $retryAfter = $this->retryAfter($exception);

                if ($retryAfter !== null && $attempts < self::MAX_RATE_LIMIT_RETRIES) {
                    $attempts++;
                    sleep($retryAfter);

                    continue;
                }

                throw CouldNotSendNotification::telegramRespondedWithAnError($exception);
            } catch (Exception $exception) {
                throw CouldNotSendNotification::couldNotCommunicateWithTelegram($exception->getMessage());
            }
        } while (true);
    }

    /**
     * Determine how long to wait before retrying a rate limited request.
     *
     * Returns null when the response is not a retryable 429 (either a
     * different status code, or a `retry_after` beyond the wait cap).
     */
    private function retryAfter(BadResponseException $exception): ?int
    {
        $response = $exception->getResponse();

        if ($response->getStatusCode() !== 429) {
            return null;
        }

        $decoded = json_decode((string) $response->getBody(), true);
        $response->getBody()->rewind();

        $parameters = is_array($decoded) && isset($decoded['parameters']) && is_array($decoded['parameters'])
            ? $decoded['parameters']
            : [];
        $retryAfter = $parameters['retry_after'] ?? 1;
        $retryAfter = is_int($retryAfter) ? $retryAfter : 1;

        return $retryAfter <= self::MAX_RETRY_AFTER ? $retryAfter : null;
    }

    /**
     * Normalize the params into Guzzle's `form_params` shape.
     *
     * Array values are JSON encoded, as expected by the Bot API for
     * structured parameters (e.g. `reply_markup`, `message_ids`). Values
     * that cannot be url-encoded (objects, resources) are dropped.
     *
     * @param  array<string, mixed>|list<array{name: string, contents: mixed, filename?: string}>  $params
     * @return array<string, bool|float|int|string|null>
     *
     * @throws JsonException When JSON encoding fails
     */
    private function formParams(array $params): array
    {
        $formParams = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $formParams[(string) $key] = json_encode($value, JSON_THROW_ON_ERROR);
            } elseif (is_scalar($value) || $value === null) {
                $formParams[(string) $key] = $value;
            }
        }

        return $formParams;
    }

    /**
     * Normalize the params into Guzzle's `multipart` shape.
     *
     * Already normalized items are passed through untouched, any other
     * key/value pair is turned into a multipart item.
     *
     * @param  array<string, mixed>|list<array{name: string, contents: mixed, filename?: string}>  $params
     * @return list<array{name: string, contents: mixed, filename?: string}>
     */
    private function multipartParams(array $params): array
    {
        $multipart = [];

        foreach ($params as $key => $value) {
            if (is_array($value) && is_string($value['name'] ?? null) && array_key_exists('contents', $value)) {
                $item = ['name' => $value['name'], 'contents' => $value['contents']];

                if (is_string($value['filename'] ?? null)) {
                    $item['filename'] = $value['filename'];
                }

                $multipart[] = $item;

                continue;
            }

            $multipart[] = ['name' => (string) $key, 'contents' => $value];
        }

        return $multipart;
    }
}
