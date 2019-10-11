<?php

namespace NotificationChannels\Telegram;

use JsonSerializable;
use NotificationChannels\Telegram\Traits\HasSharedLogic;

/**
 * Class TelegramMessage
 */
class TelegramMessage implements JsonSerializable
{
    use HasSharedLogic;

    /**
     * @param  string  $content
     *
     * @return self
     */
    public static function create($content = ''): self
    {
        return new self($content);
    }

    /**
     * Message constructor.
     *
     * @param  string  $content
     */
    public function __construct($content = '')
    {
        $this->content($content);
        $this->payload['parse_mode'] = 'Markdown';
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @param $content
     *
     * @return $this
     */
    public function content($content): self
    {
        $this->payload['text'] = $content;

        return $this;
    }
}
