<?php

namespace NotificationChannels\Telegram;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Str;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Telegram.
 */
class Telegram
{
    /** @var HttpClient HTTP Client */
    protected $http;

    /** @var null|string Telegram Bot API Token. */
    protected $token;

    /**
     * @param null            $token
     * @param HttpClient|null $httpClient
     */
    public function __construct($token = null, HttpClient $httpClient = null)
    {
        $this->token = $token;
        $this->http = $httpClient;
    }

    /**
     * Token getter.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Token setter.
     *
     * @param  string
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * Get HttpClient.
     *
     * @return HttpClient
     */
    protected function httpClient(): HttpClient
    {
        return $this->http ?? new HttpClient();
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
     * @link https://core.telegram.org/bots/api#sendmessage
     *
     * @param array $params
     *
     * @throws CouldNotSendNotification
     *
     * @return ResponseInterface|null
     */
    public function sendMessage(array $params): ?ResponseInterface
    {
        return $this->sendRequest('sendMessage', $params);
    }

    /**
     * Send File as Image or Document.
     *
     * @param array  $params
     * @param string $type
     * @param bool   $multipart
     *
     * @throws CouldNotSendNotification
     *
     * @return ResponseInterface|null
     */
    public function sendFile(array $params, string $type, bool $multipart = false): ?ResponseInterface
    {
        return $this->sendRequest('send'.Str::studly($type), $params, $multipart);
    }

    /**
     * Send a Location.
     *
     * @param array $params
     *
     * @throws CouldNotSendNotification
     *
     * @return ResponseInterface|null
     */
    public function sendLocation(array $params): ?ResponseInterface
    {
        return $this->sendRequest('sendLocation', $params);
    }

    /**
     * Send an API request and return response.
     *
     * @param string $endpoint
     * @param array  $params
     * @param bool   $multipart
     *
     * @throws CouldNotSendNotification
     *
     * @return ResponseInterface|null
     */
    protected function sendRequest(string $endpoint, array $params, bool $multipart = false): ?ResponseInterface
    {
        if (blank($this->token)) {
            throw CouldNotSendNotification::telegramBotTokenNotProvided('You must provide your telegram bot token to make any API requests.');
        }

        $endPointUrl = 'https://api.telegram.org/bot'.$this->token.'/'.$endpoint;

        try {
            return $this->httpClient()->post($endPointUrl, [
                $multipart ? 'multipart' : 'form_params' => $params,
            ]);
        } catch (ClientException $exception) {
            throw CouldNotSendNotification::telegramRespondedWithAnError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithTelegram($exception);
        }
    }
}
