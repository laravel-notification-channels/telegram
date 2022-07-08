<?php

namespace NotificationChannels\Telegram;

use Illuminate\Support\Facades\View;
use JsonSerializable;
use NotificationChannels\Telegram\Traits\HasSharedLogic;

/**
 * Class TelegramMessage.
 */
class TelegramMessage implements JsonSerializable
{
    use HasSharedLogic;

    /** @var int Message Chunk Size */
    public $chunkSize;

    public function __construct(string $content = '', string $parse_mode = 'Markdown')
    {
        $this->content($content);
        $this->payload['parse_mode'] = $parse_mode;
    }

    public static function create(string $content = '', string $parse_mode = 'Markdown'): self
    {
        return new self($content, $parse_mode);
    }

    /**
     * Parse mode message.
     *
     * @return $this
     */
    public function parseMode(string $parse_mode = 'Markdown'): self
    {
        $this->payload['parse_mode'] = $parse_mode;

        return $this;
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @return $this
     */
    public function content(string $content, int $limit = null): self
    {
        $this->payload['text'] = $content;

        if ($limit) {
            $this->chunkSize = $limit;
        }

        return $this;
    }

    /**
     * Attach a view file as the content for the notification.
     * Supports Laravel blade template.
     *
     * @return $this
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->content(View::make($view, $data, $mergeData)->render());
    }

    /**
     * Chunk message to given size.
     *
     * @return $this
     */
    public function chunk(int $limit = 4096): self
    {
        $this->chunkSize = $limit;

        return $this;
    }

    /**
     * Should the message be chunked.
     */
    public function shouldChunk(): bool
    {
        return null !== $this->chunkSize;
    }
}
