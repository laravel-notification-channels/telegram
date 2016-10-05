<?php

namespace NotificationChannels\Telegram;

class TelegramPhoto
{
    /**
     * @var array Params payload.
     */
    public $payload = [];

    /**
     * @var array Inline Keyboard Buttons.
     */
    protected $buttons = [];

    /**
     * @param string $content
     *
     * @return static
     */
    public static function create($content = '')
    {
        return new static($content);
    }

    /**
     * Message constructor.
     *
     * @param string $photo
     */
    public function __construct($photo = '')
    {
        $this->photo($photo);
    }

    /**
     * Recipient's Chat ID.
     *
     * @param $chatId
     *
     * @return $this
     */
    public function to($chatId)
    {
        $this->payload['chat_id'] = $chatId;

        return $this;
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @param $photo
     *
     * @return $this
     */
    public function photo($photo)
    {
        $this->payload['photo'] = $photo;

        return $this;
    }
    /**
     * Notification message (Supports Markdown).
     *
     * @param $caption
     *
     * @return $this
     */
    public function caption($caption)
    {
        $this->payload['caption'] = $caption;

        return $this;
    }

    /**
     * Add an inline button.
     *
     * @param string $text
     * @param string $url
     *
     * @return $this
     */
    public function button($text, $url)
    {
        $this->buttons[] = compact('text', 'url');

        $replyMarkup['inline_keyboard'] = array_chunk($this->buttons, 2);
        $this->payload['reply_markup'] = json_encode($replyMarkup);

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
        $this->payload = array_merge($this->payload, $options);

        return $this;
    }

    /**
     * Determine if chat id is not given.
     *
     * @return bool
     */
    public function toNotGiven()
    {
        return !isset($this->payload['chat_id']);
    }

    /**
     * Send message.
     *
     * @param Telegram $telegram
     */
    public function send(Telegram $telegram)
    {
        $telegram->sendPhoto($this->payload);
    }
}
