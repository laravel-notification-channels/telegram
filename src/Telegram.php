<?php

namespace NotificationChannels\Telegram;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

class Telegram
{
    /**
     * @var HttpClient HTTP Client
     */
    protected $http;

    /**
     * @var null|string Telegram Bot API Token.
     */
    protected $token = null;

    /**
     * @var array Keyboard Markup
     */
    protected $keyboard = [];

    /**
     * Telegram constructor.
     *
     * @param null                     $token
     * @param HttpClient|null $httpClient
     */
    public function __construct($token = null, HttpClient $httpClient = null)
    {
        $this->token = $token;
        $this->http = $httpClient;
    }

    /**
     * Set Telegram Bot API Token.
     *
     * @param $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get Telegram Bot API Token.
     *
     * @return null|string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get HttpClient.
     *
     * @return HttpClient
     */
    protected function httpClient()
    {
        return $this->http ?: $this->http = new HttpClient();
    }

    /**
     * Create a new row for keyboard markup with inline buttons.
     *
     * @return $this
     */
    public function buttons()
    {
        $this->keyboard['inline_keyboard'][] = func_get_args();

        return $this;
    }

    /**
     * Get Keyboard Markup.
     *
     * @param int $options
     *
     * @return string
     */
    public function getKeyboardMarkup($options = 0)
    {
        return json_encode($this->keyboard, $options);
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
     * @var int|string $params ['chat_id']
     * @var string     $params ['text']
     * @var string     $params ['parse_mode']
     * @var bool       $params ['disable_web_page_preview']
     * @var bool       $params ['disable_notification']
     * @var int        $params ['reply_to_message_id']
     * @var string     $params ['reply_markup']
     *
     * @return mixed
     */
    public function sendMessage($params)
    {
        return $this->sendRequest('sendMessage', $params);
    }

    /**
     * Send an API request and return response.
     *
     * @param $endpoint
     * @param $params
     *
     * @throws CouldNotSendNotification
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function sendRequest($endpoint, $params)
    {
        $this->isTokenExist();

        try {
            return $this->httpClient()->post($this->prepareApiUrl($endpoint), [
                'form_params' => $params,
            ]);
        } catch (ClientException $e) {
            throw CouldNotSendNotification::telegramRespondedWithAnError($e);
        } catch (\Exception $e) {
            throw CouldNotSendNotification::serviceCommunicationError();
        }
    }

    /**
     * Prepare API URL.
     *
     * @param string $endpoint
     *
     * @return string
     */
    protected function prepareApiUrl($endpoint)
    {
        return 'https://api.telegram.org/bot'.$this->token.'/'.$endpoint;
    }

    /**
     * Determines telegram bot token exists.
     *
     * @throws TelegramChannelException
     */
    protected function isTokenExist()
    {
        $token = $this->getToken();

        if ($token === null) {
            throw CouldNotSendNotification::telegramBotTokenNotProvided('You must provide your telegram bot token to make any API requests.');
        }
    }
}
