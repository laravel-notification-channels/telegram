<?php

namespace NotificationChannels\Telegram;

class Message
{
    /**
     * The Telegram Chat ID the message should be sent to.
     *
     * @var string
     */
    public $chatId;

    /**
     * The message content.
     *
     * @var string
     */
    public $content;

    /**
     * The text / label for the action.
     *
     * @var string
     */
    public $actionText;

    /**
     * The action URL.
     *
     * @var string
     */
    public $actionUrl;

    /**
     * Additional options to be passed to the sendMessage method.
     *
     * @var array
     */
    public $options = [];

    /**
     * Message constructor.
     *
     * @param string $content
     */
    public function __construct($content = '')
    {
        $this->content = $content;
    }

    /**
     * @param $chatId
     *
     * @return $this
     */
    public function to($chatId)
    {
        $this->chatId = $chatId;

        return $this;
    }

    /**
     * @param $content
     *
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Configure the "call to action" button.
     *
     * @param  string $text
     * @param  string $url
     *
     * @return $this
     */
    public function action($text, $url)
    {
        $this->actionText = $text;
        $this->actionUrl = $url;

        return $this;
    }

    /**
     * Additional options to pass to sendMessage method.
     *
     * @param array $options
     *
     * @return $this
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }
}
