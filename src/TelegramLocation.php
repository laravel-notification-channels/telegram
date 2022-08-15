<?php

namespace NotificationChannels\Telegram;

use NotificationChannels\Telegram\Contracts\TelegramSender;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

/**
 * Class TelegramLocation.
 */
class TelegramLocation extends TelegramBase implements TelegramSender
{
    /**
     * Telegram Location constructor.
     *
     * @param null|float|string $latitude
     * @param null|float|string $longitude
     */
    public function __construct($latitude = null, $longitude = null)
    {
        parent::__construct();
        $this->latitude($latitude);
        $this->longitude($longitude);
    }

    /**
     * @param null|float|string $latitude
     * @param null|float|string $longitude
     *
     * @return static
     */
    public static function create($latitude = null, $longitude = null): self
    {
        return new static($latitude, $longitude);
    }

    /**
     * Location's latitude.
     *
     * @param float|string $latitude
     *
     * @return $this
     */
    public function latitude($latitude): self
    {
        $this->payload['latitude'] = $latitude;

        return $this;
    }

    /**
     * Location's latitude.
     *
     * @param float|string $longitude
     *
     * @return $this
     */
    public function longitude($longitude): self
    {
        $this->payload['longitude'] = $longitude;

        return $this;
    }

    /**
     * @throws CouldNotSendNotification
     */
    public function send()
    {
        return $this->telegram->sendLocation($this->toArray());
    }
}
