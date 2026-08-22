<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use Illuminate\Support\Facades\View;
use JsonException;
use NotificationChannels\Telegram\Contracts\TelegramSenderContract;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

final class TelegramMessage extends TelegramBase implements TelegramSenderContract
{
    private const DEFAULT_CHUNK_SIZE = 4096;

    private string $text = '';

    public function __construct(
        string $content = '',
        public int $chunkSize = 0
    ) {
        parent::__construct();

        $this->text = $content;
        $this->parseMode(ParseMode::Markdown);
    }

    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    /** @see https://core.telegram.org/bots/api#markdownv2-style */
    public static function escapeMarkdown(string $content): ?string
    {
        return preg_replace_callback(
            '/[_*[\]()~`>#\+\-=|{}.!]/',
            fn ($matches): string => "\\$matches[0]",
            $content
        );
    }

    /** @see https://core.telegram.org/bots/api#markdown-style */
    public static function escapeLegacyMarkdown(string $content): string
    {
        return strtr($content, [
            '_' => '\_',
            '*' => '\*',
            '`' => '\`',
            '[' => '\[',
        ]);
    }

    public function content(string $content, ?int $limit = null): self
    {
        $this->text = $content;

        if ($limit !== null) {
            $this->chunkSize = $limit;
        }

        return $this;
    }

    public function line(string $content): self
    {
        $this->text .= $content."\n";

        return $this;
    }

    public function lineIf(bool $condition, string $line): self
    {
        if ($condition) {
            $this->line($line);
        }

        return $this;
    }

    /**
     * Append a line, escaped for the currently set parse mode.
     *
     * Legacy Markdown only supports escaping `_`, `*`, `` ` `` and `[`;
     * MarkdownV2 gets the full special character set. Lines are appended
     * untouched for HTML or when no parse mode is set.
     */
    public function escapedLine(string $content): self
    {
        $escaped = match ($this->getPayloadValue('parse_mode')) {
            ParseMode::MarkdownV2->value => self::escapeMarkdown(
                str_replace('\\', '\\\\', $content)
            ) ?? $content,
            ParseMode::Markdown->value => self::escapeLegacyMarkdown($content),
            default => $content,
        };

        return $this->line($escaped);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->content(View::make($view, $data, $mergeData)->render());
    }

    /**
     * @param  list<array<string, mixed>>  $entities
     *
     * @throws JsonException When JSON encoding fails
     */
    public function entities(array $entities): self
    {
        return $this->jsonPayload('entities', $entities);
    }

    /**
     * @param  array<string, mixed>  $linkPreviewOptions
     *
     * @throws JsonException When JSON encoding fails
     */
    public function linkPreviewOptions(array $linkPreviewOptions): self
    {
        return $this->jsonPayload('link_preview_options', $linkPreviewOptions);
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
     * @return array<int, array<string, mixed>>|ResponseInterface
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function send(): array|ResponseInterface
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->toArray();

        return $this->shouldChunk()
            ? $this->sendChunkedMessage($payload)
            : $this->telegram->sendMessage($payload);
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
        $replyMarkup = $params['reply_markup'] ?? null;

        if ($replyMarkup !== null) {
            unset($params['reply_markup']);
        }

        $messages = $this->chunkStrings($this->text, $this->chunkSize);

        $lastIndex = count($messages) - 1;
        $responses = [];

        foreach ($messages as $index => $message) {
            $payload = [...$params, 'text' => $message];

            if ($index === $lastIndex && $replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $responses[] = Telegram::decodeResponse($this->telegram->sendMessage($payload));

            // Telegram rate limiting safety between chunks
            if ($index !== $lastIndex) {
                sleep(1); // @pest-mutate-ignore
            }
        }

        return $responses;
    }

    /**
     * Split text into chunks of at most `$limit` characters.
     *
     * Prefers breaking at the last newline within the chunk, then the last
     * space, and only hard-splits when neither is available. Note: a break
     * can still land inside a Markdown entity (e.g. an unclosed `*bold*`),
     * which Telegram rejects — prefer chunk-sized paragraphs or `normal()`
     * for machine-generated content.
     *
     * @return list<string>
     */
    private function chunkStrings(string $value, int $limit = self::DEFAULT_CHUNK_SIZE): array
    {
        // chunkSize is always >= 1 here (shouldChunk() gates on > 0), so
        // only the upper bound needs clamping.
        $limit = min($limit, self::DEFAULT_CHUNK_SIZE);

        if (mb_strlen($value, 'UTF-8') <= $limit) { // @pest-mutate-ignore
            return [$value]; // @pest-mutate-ignore
        }

        $chunks = [];
        $remaining = $value;

        while (mb_strlen($remaining, 'UTF-8') > $limit) {
            $slice = mb_substr($remaining, 0, $limit, 'UTF-8');

            $breakAt = null;
            foreach (["\n", ' '] as $delimiter) {
                $position = mb_strrpos($slice, $delimiter, encoding: 'UTF-8');

                if ($position !== false && $position > 0) { // @pest-mutate-ignore
                    $breakAt = $position;
                    break;
                }
            }

            if ($breakAt === null) {
                $chunks[] = $slice;
                $remaining = mb_substr($remaining, $limit, null, 'UTF-8');

                continue;
            }

            $chunks[] = mb_substr($remaining, 0, $breakAt, 'UTF-8');
            $remaining = mb_substr($remaining, $breakAt + 1, null, 'UTF-8');
        }

        // A break is never chosen at the final character, so the tail is
        // always non-empty here.
        $chunks[] = $remaining;

        return $chunks;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            ...parent::toArray(),
        ];
    }

    public function getPayloadValue(string $key): string|int|float|bool|array|null
    {
        return match ($key) {
            'text' => $this->text,
            default => parent::getPayloadValue($key),
        };
    }
}
