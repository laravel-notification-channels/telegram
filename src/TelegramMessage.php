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

    public function __construct(string $content = '')
    {
        $this->content($content);
        $this->payload['parse_mode'] = Telegram::MARKDOWN_PARSE_MODE;
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    /**
     * Notification simple message (Without Markdown support).
     *
     * @param string   $content
     * @param int|null $limit
     *
     * @return $this
     */
    public function text(string $content, int $limit = null): self
    {
        return $this->content($content, $limit)->htmlParseMode();
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
     * Only html, without Markdown.
     *
     * @param string $view
     * @param array  $data
     * @param array  $mergeData
     *
     * @return $this
     */
    public function viewHtml(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->view($view, $data, $mergeData)->htmlParseMode();
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
