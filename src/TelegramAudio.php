<?php

namespace NotificationChannels\Telegram;

use JsonSerializable;
use NotificationChannels\Telegram\Traits\HasSharedLogic;

/**
 * Class TelegramAudio.
 */
class TelegramAudio implements JsonSerializable
{
    use HasSharedLogic;

    /**
     * Message constructor.
     */
    public function __construct(string $audio = '')
    {
        $this->audio($audio);
        $this->payload['parse_mode'] = 'Markdown';
    }

    public static function create(string $audio = ''): self
    {
        return new self($audio);
    }

    /**
     * Poll audio.
     *
     * @return $this
     */
    public function audio(string $audio): self
    {
        $this->payload['audio'] = $audio;

        return $this;
    }
}
