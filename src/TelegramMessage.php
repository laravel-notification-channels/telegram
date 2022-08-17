<?php

namespace NotificationChannels\Telegram;

use Illuminate\Support\Facades\View;
use NotificationChannels\Telegram\Contracts\TelegramSender;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

/**
 * Class TelegramMessage.
 */
class TelegramMessage extends TelegramBase implements TelegramSender
{
    /** @var int Message Chunk Size */
    public $chunkSize;

    public function __construct(string $content = '')
    {
        parent::__construct();
        $this->content($content);
        $this->payload['parse_mode'] = 'Markdown';
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
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

    /**
     * @throws CouldNotSendNotification
     */
    public function send()
    {
        $params = $this->toArray();

        if ($this->shouldChunk()) {
            $replyMarkup = $this->getPayloadValue('reply_markup');

            if ($replyMarkup) {
                unset($params['reply_markup']);
            }

            $messages = $this->chunkStrings($this->getPayloadValue('text'), $this->chunkSize);

            $payloads = collect($messages)->filter()->map(function ($text) use ($params) {
                return array_merge($params, ['text' => $text]);
            });

            if ($replyMarkup) {
                $lastMessage = $payloads->pop();
                $lastMessage['reply_markup'] = $replyMarkup;
                $payloads->push($lastMessage);
            }

            return $payloads->map(function ($payload) {
                $response = $this->telegram->sendMessage($payload);

                // To avoid rate limit of one message per second.
                sleep(1);

                if ($response) {
                    return json_decode($response->getBody()->getContents(), true);
                }

                return $response;
            })->toArray();
        }

        return $this->telegram->sendMessage($params);
    }

    /**
     * Chunk the given string into an array of strings.
     */
    private function chunkStrings(string $value, int $limit = 4096): array
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return [$value];
        }

        if ($limit >= 4097) {
            $limit = 4096;
        }

        $output = explode('%#TGMSG#%', wordwrap($value, $limit, '%#TGMSG#%'));

        // Fallback for when the string is too long and wordwrap doesn't cut it.
        if (count($output) <= 1) {
            $output = mb_str_split($value, $limit, 'UTF-8');
        }

        return $output;
    }
}
