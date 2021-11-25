<?php

namespace NotificationChannels\Telegram;

/**
 * Class TelegramUpdates.
 */
class TelegramUpdates
{
    /**
     * @var array
     */
    private $payload = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Telegram updates limit.
     *
     * @return $this
     */
    public function limit(int $limit = null): self
    {
        $this->payload['limit'] = $limit;

        return $this;
    }

    /**
     * Additional options.
     *
     * @return $this
     */
    public function options(array $options): self
    {
        $this->payload = array_merge($this->payload, $options);

        return $this;
    }

    public function get(): array
    {
        $response = (new Telegram())->setToken(config('services.telegram-bot-api.token'))->getUpdates($this->payload);

        return json_decode($response->getBody()->getContents(), true);
    }
}
