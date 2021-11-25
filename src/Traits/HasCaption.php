<?php

namespace NotificationChannels\Telegram\Traits;

/**
 * Trait HasCaption.
 */
trait HasCaption
{
    /**
     * Add a caption.
     *
     * @param string $caption
     *
     * @return $this
     */
    public function caption($caption): self
    {
        $this->payload['caption'] = $caption;

        return $this;
    }
}
