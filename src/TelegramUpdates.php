<?php

namespace NotificationChannels\Telegram;

use JsonSerializable;
use NotificationChannels\Telegram\Traits\HasSharedLogic;

/**
 * Class TelegramUpdates.
 */
class TelegramUpdates implements JsonSerializable
{
    use HasSharedLogic;

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
     * @return array
     */
    public function get(): array
    {
        $response = (new Telegram())->setToken(config('services.telegram-bot-api.token'))
            ->getUpdates($this->payload);

        return json_decode($response->getBody()->getContents(), true);
    }
}
