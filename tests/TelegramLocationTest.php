<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramLocation;
use PHPUnit\Framework\TestCase;

/**
 * Class TelegramLocationTest.
 *
 * @internal
 *
 * @coversNothing
 */
class TelegramLocationTest extends TestCase
{
    public const TEST_LONG = -77.0364;

    public const TEST_LAT = 38.8951;

    /** @test */
    public function itAcceptsContentWhenConstructed(): void
    {
        $message = new TelegramLocation(self::TEST_LAT, self::TEST_LONG);
        $this->assertEquals(self::TEST_LAT, $message->getPayloadValue('latitude'));
        $this->assertEquals(self::TEST_LONG, $message->getPayloadValue('longitude'));
    }

    /** @test */
    public function theRecipientsChatIdCanBeSet(): void
    {
        $message = new TelegramLocation();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function theNotificationLongitudeCanBeSet(): void
    {
        $message = new TelegramLocation();
        $message->longitude(self::TEST_LONG);
        $this->assertEquals(self::TEST_LONG, $message->getPayloadValue('longitude'));
    }

    /** @test */
    public function theNotificationLatitudeCanBeSet(): void
    {
        $message = new TelegramLocation();
        $message->latitude(self::TEST_LAT);
        $this->assertEquals(self::TEST_LAT, $message->getPayloadValue('latitude'));
    }

    /** @test */
    public function additionalOptionsCanBeSetForTheMessage(): void
    {
        $message = new TelegramLocation();
        $message->options(['foo' => 'bar']);
        $this->assertEquals('bar', $message->getPayloadValue('foo'));
    }

    /** @test */
    public function itCanDetermineIfTheRecipientChatIdHasNotBeenSet(): void
    {
        $message = new TelegramLocation();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function itCanReturnThePayloadAsAnArray(): void
    {
        $message = new TelegramLocation(self::TEST_LAT, self::TEST_LONG);
        $message->to(12345);
        $message->options(['foo' => 'bar']);
        $expected = [
            'chat_id' => 12345,
            'foo' => 'bar',
            'longitude' => self::TEST_LONG,
            'latitude' => self::TEST_LAT,
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}
