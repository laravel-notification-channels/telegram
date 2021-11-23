<?php

namespace NotificationChannels\Telegram;

use JsonSerializable;
use NotificationChannels\Telegram\Traits\HasSharedLogic;

/**
 * Class TelegramPoll.
 */
class TelegramPoll implements JsonSerializable
{
    use HasSharedLogic;

    /**
     * @param  string  $question
     *
     * @return self
     */
    public static function create(string $question = ''): self
    {
        return new self($question);
    }

    /**
     * Message constructor.
     *
     * @param  string  $question
     */
    public function __construct(string $question = '')
    {
        $this->question($question);
    }

    /**
     * Poll question.
     *
     * @param  string  $question
     *
     * @return $this
     */
    public function question(string $question): self
    {
        $this->payload['question'] = $question;

        return $this;
    }

    /**
     * Poll choices.
     *
     * @param  array  $choices
     *
     * @return $this
     */
    public function choices(array $choices): self
    {
        $this->payload['options'] = json_encode($choices);

        return $this;
    }
}
