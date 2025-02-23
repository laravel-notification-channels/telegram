<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use JsonException;
use NotificationChannels\Telegram\Contracts\TelegramSenderContract;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

final class TelegramMessage extends TelegramBase implements TelegramSenderContract
{
    private const DEFAULT_CHUNK_SIZE = 4096;

    private const CHUNK_SEPARATOR = '%#TGMSG#%';

    public function __construct(
        string $content = '',
        public int $chunkSize = 0
    ) {
        parent::__construct();
        $this->content($content);
        $this->parseMode(ParseMode::Markdown);
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    public function content(string $content, ?int $limit = null): self
    {
        $this->payload['text'] = $content;
        if ($limit !== null) {
            $this->chunkSize = $limit;
        }

        return $this;
    }

    public function line(string $content): self
    {
        $this->payload['text'] .= "$content\n";

        return $this;
    }

    public function lineIf(bool $condition, string $line): self
    {
        return $condition ? $this->line($line) : $this;
    }

    public function escapedLine(string $content): self
    {
        $content = str_replace('\\', '\\\\', $content);

        $escapedContent = preg_replace_callback(
            '/[_*[\]()~`>#\+\-=|{}.!]/',
            fn ($matches): string => "\\$matches[0]",
            $content
        );

        return $this->line($escapedContent ?? $content);
    }

    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->content(View::make($view, $data, $mergeData)->render());
    }

    public function chunk(int $limit = self::DEFAULT_CHUNK_SIZE): self
    {
        $this->chunkSize = $limit;

        return $this;
    }

    public function shouldChunk(): bool
    {
        return $this->chunkSize > 0;
    }

    /**
     * @return array<int, array<string, mixed>>|ResponseInterface|null
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function send(): array|ResponseInterface|null
    {
        return $this->shouldChunk()
            ? $this->sendChunkedMessage($this->toArray())
            : $this->telegram->sendMessage($this->toArray());
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    private function sendChunkedMessage(array $params): array
    {
        $replyMarkup = $this->getPayloadValue('reply_markup');

        if ($replyMarkup) {
            unset($params['reply_markup']);
        }

        $messages = $this->chunkStrings($params['text'], $this->chunkSize);
        $lastIndex = count($messages) - 1;

        return Collection::make($messages)
            ->filter()
            ->map(function (string $text, int $index) use ($params, $replyMarkup, $lastIndex): ?array {
                $payload = [...$params, 'text' => $text];
                if ($index === $lastIndex && $replyMarkup !== null) {
                    $payload['reply_markup'] = $replyMarkup;
                }

                $response = $this->telegram->sendMessage($payload);
                sleep(1); // Rate limiting

                return $response ? json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ) : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function chunkStrings(string $value, int $limit = self::DEFAULT_CHUNK_SIZE): array
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return [$value];
        }

        $limit = min($limit, self::DEFAULT_CHUNK_SIZE);

        $output = explode(self::CHUNK_SEPARATOR, wordwrap($value, $limit, self::CHUNK_SEPARATOR));

        return count($output) <= 1
            ? mb_str_split($value, $limit, 'UTF-8')
            : $output;
    }
}
