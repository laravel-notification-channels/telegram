<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use JsonException;

/**
 * Class TelegramUpdates.
 */
class TelegramUpdates
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected array $payload = [],
        protected ?Telegram $telegram = null
    ) {}

    public static function create(?Telegram $telegram = null): self
    {
        return new self(telegram: $telegram);
    }

    /**
     * Telegram updates limit.
     *
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->payload['limit'] = $limit;

        return $this;
    }

    /**
     * Additional options.
     *
     * @param  array<string, mixed>  $options
     * @return $this
     */
    public function options(array $options): self
    {
        $this->payload = [...$this->payload, ...$options];

        return $this;
    }

    public function latest(): self
    {
        $this->payload['offset'] = -1;

        return $this;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function get(): array
    {
        $telegram = $this->telegram ?? app(Telegram::class);

        return Telegram::decodeResponse(
            $telegram->getUpdates($this->payload)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
