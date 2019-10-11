<?php

namespace NotificationChannels\Telegram\Test;

use PHPUnit\Framework\TestCase;
use NotificationChannels\Telegram\TelegramLocation;

/**
 * Class TelegramLocationTest.
 */
class TelegramLocationTest extends TestCase
{
    public const TEST_LONG = -77.0364;
    public const TEST_LAT = 38.8951;

    /** @test */
    public function it_accepts_content_when_constructed(): void
    {
        $message = new TelegramLocation(self::TEST_LAT, self::TEST_LONG);
        $this->assertEquals(self::TEST_LAT, $message->getPayloadValue('latitude'));
        $this->assertEquals(self::TEST_LONG, $message->getPayloadValue('longitude'));
    }

    /** @test */
    public function the_recipients_chat_id_can_be_set(): void
    {
        $message = new TelegramLocation();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function the_notification_longitude_can_be_set(): void
    {
        $message = new TelegramLocation();
        $message->longitude(self::TEST_LONG);
        $this->assertEquals(self::TEST_LONG, $message->getPayloadValue('longitude'));
    }

    /** @test */
    public function the_notification_latitude_can_be_set(): void
    {
        $message = new TelegramLocation();
        $message->latitude(self::TEST_LAT);
        $this->assertEquals(self::TEST_LAT, $message->getPayloadValue('latitude'));
    }

    /** @test */
    public function additional_options_can_be_set_for_the_message(): void
    {
        $message = new TelegramLocation();
        $message->options(['foo' => 'bar']);
        $this->assertEquals('bar', $message->getPayloadValue('foo'));
    }

    /** @test */
    public function it_can_determine_if_the_recipient_chat_id_has_not_been_set(): void
    {
        $message = new TelegramLocation();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function it_can_return_the_payload_as_an_array(): void
    {
        $message = new TelegramLocation(self::TEST_LAT, self::TEST_LONG);
        $message->to(12345);
        $message->options(['foo' => 'bar']);
        $expected = [
            'chat_id'   => 12345,
            'foo'       => 'bar',
            'longitude' => self::TEST_LONG,
            'latitude'  => self::TEST_LAT,
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}
