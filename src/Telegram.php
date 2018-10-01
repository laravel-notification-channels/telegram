<?php

namespace NotificationChannels\Telegram;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use GuzzleHttp\Post\PostFile;

class Telegram
{
    /** @var HttpClient HTTP Client */
    protected $http;

    /** @var null|string Telegram Bot API Token. */
    protected $token = null;

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
     * Get HttpClient.
     *
     * @return HttpClient
     */
    protected function httpClient()
    {
        return $this->http ?: $this->http = new HttpClient();
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
    * Send File as Image or Document
    *
    * @param array $params
    * @param string $type
    * @param bool $multipart
    * 
    * @return mixed
    * 
    */
    public function sendFile($params, $type, $multipart = false)
    {
        return $this->sendRequest('send'.ucfirst($type), $params, $multipart);
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
        if (empty($this->token)) {
            throw CouldNotSendNotification::telegramBotTokenNotProvided('You must provide your telegram bot token to make any API requests.');
        }

        $endPointUrl = 'https://api.telegram.org/bot'.$this->token.'/'.$endpoint;

        try {
            if($multipart)
                $post_name = 'multipart';
            else
                $post_name = 'form_params';

            return $this->httpClient()->post($endPointUrl, [
                $post_name => $params,
            ]);

        } catch (ClientException $exception) {
            throw CouldNotSendNotification::telegramRespondedWithAnError($exception);
        } catch (\Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithTelegram($exception);
        }
    }
}
