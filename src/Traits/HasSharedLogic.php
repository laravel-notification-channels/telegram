<?php

namespace NotificationChannels\Telegram\Traits;

use Illuminate\Support\Traits\Conditionable;

/**
 * Trait HasSharedLogic.
 */
trait HasSharedLogic
{
    use Conditionable;

    /** @var null|string Bot Token. */
    public ?string $token = null;

    /** @var array Params payload. */
    protected array $payload = [];

    /** @var array Inline Keyboard Buttons. */
    protected array $buttons = [];

    /**
     * Recipient's Chat ID.
     *
     * @param  int|string  $chatId
     * @return static
     */
    public function to(int|string $chatId): self
    {
        $this->payload['chat_id'] = $chatId;

        return $this;
    }

    /**
     * Add an inline button.
     *
     * @param  string  $text
     * @param  string  $url
     * @param  int  $columns
     * @return static
     *
     * @throws \JsonException
     */
    public function button(string $text, string $url, int $columns = 2): self
    {
        $this->buttons[] = compact('text', 'url');

        $this->payload['reply_markup'] = json_encode([
            'inline_keyboard' => array_chunk($this->buttons, $columns),
        ], JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * Add an inline button with callback_data.
     *
     * @param  string  $text
     * @param  string  $callback_data
     * @param  int  $columns
     * @return static
     *
     * @throws \JsonException
     */
    public function buttonWithCallback(string $text, string $callback_data, int $columns = 2): self
    {
        $this->buttons[] = compact('text', 'callback_data');

        $this->payload['reply_markup'] = json_encode([
            'inline_keyboard' => array_chunk($this->buttons, $columns),
        ], JSON_THROW_ON_ERROR);

        return $this;
    }

    /**
     * Send the message silently.
     * Users will receive a notification with no sound.
     *
     * @param  bool  $disableNotification
     * @return static
     */
    public function disableNotification(bool $disableNotification = true): self
    {
        $this->payload['disable_notification'] = $disableNotification;

        return $this;
    }

    /**
     * Bot Token.
     * Overrides default bot token with the given value for this notification.
     *
     * @param  string  $token
     * @return static
     */
    public function token(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Determine if bot token is given for this notification.
     */
    public function hasToken(): bool
    {
        return null !== $this->token;
    }

    /**
     * Additional options to pass to sendMessage method.
     *
     * @param  array  $options
     * @return static
     */
    public function options(array $options): self
    {
        $this->payload = array_merge($this->payload, $options);

        return $this;
    }

    /**
     * Determine if chat id is not given.
     */
    public function toNotGiven(): bool
    {
        return ! isset($this->payload['chat_id']);
    }

    /**
     * Get payload value for given key.
     *
     * @return null|mixed
     */
    public function getPayloadValue(string $key): mixed
    {
        return $this->payload[$key] ?? null;
    }

    /**
     * Returns params payload.
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
