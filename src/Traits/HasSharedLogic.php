<?php

namespace NotificationChannels\Telegram\Traits;

use Illuminate\Support\Traits\Conditionable;

/**
 * Trait HasSharedLogic.
 */
trait HasSharedLogic
{
    use Conditionable;

    /** @var string Bot Token. */
    public $token;

    /** @var array Params payload. */
    protected $payload = [];

    /** @var array Inline Keyboard Buttons. */
    protected $buttons = [];

    /**
     * Recipient's Chat ID.
     *
     * @param int|string $chatId
     *
     * @return $this
     */
    public function to($chatId): self
    {
        $this->payload['chat_id'] = $chatId;

        return $this;
    }

    /**
     * Add an inline button.
     *
     * @return $this
     */
    public function button(string $text, string $url, int $columns = 2): self
    {
        $this->buttons[] = compact('text', 'url');

        $this->payload['reply_markup'] = json_encode([
            'inline_keyboard' => array_chunk($this->buttons, $columns),
        ]);

        return $this;
    }

    /**
     * Add an inline button with callback_data.
     *
     * @return $this
     */
    public function buttonWithCallback(string $text, string $callback_data, int $columns = 2): self
    {
        $this->buttons[] = compact('text', 'callback_data');

        $this->payload['reply_markup'] = json_encode([
            'inline_keyboard' => array_chunk($this->buttons, $columns),
        ]);

        return $this;
    }

    /**
     * Send the message silently.
     * Users will receive a notification with no sound.
     *
     * @return $this
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
     * @return $this
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
     * @return $this
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
        return !isset($this->payload['chat_id']);
    }

    /**
     * Get payload value for given key.
     *
     * @return null|mixed
     */
    public function getPayloadValue(string $key)
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
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
