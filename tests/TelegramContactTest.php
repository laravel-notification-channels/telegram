<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramContact;
use PHPUnit\Framework\TestCase;

/**
 * Class TelegramContactTest.
 *
 * @internal
 * @coversNothing
 */
class TelegramContactTest extends TestCase
{
    /** @test */
    public function itAcceptsPhoneNumberWhenConstructed(): void
    {
        $message = new TelegramContact('00000000');
        $this->assertEquals('00000000', $message->getPayloadValue('phone_number'));
    }

    /** @test */
    public function theRecipientsChatIdCanBeSet(): void
    {
        $message = new TelegramContact();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function thePhoneNumberCanBeSet(): void
    {
        $message = new TelegramContact();
        $message->phoneNumber('00000000');
        $this->assertEquals('00000000', $message->getPayloadValue('phone_number'));
    }

    /** @test */
    public function theFirstNameCanBeSetForTheContact(): void
    {
        $message = new TelegramContact();
        $message->firstName('Faissal');
        $this->assertEquals('Faissal', $message->getPayloadValue('first_name'));
    }

    /** @test */
    public function theLastNameCanBeSetForTheContact(): void
    {
        $message = new TelegramContact();
        $message->lastName('Wahabali');
        $this->assertEquals('Wahabali', $message->getPayloadValue('last_name'));
    }

    /** @test */
    public function itCanDetermineIfTheRecipientChatIdHasNotBeenSet(): void
    {
        $message = new TelegramContact();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function itCanReturnThePayloadAsAnArray(): void
    {
        $message = new TelegramContact('00000000');
        $message->to(12345);
        $message->firstName('Faissal');
        $message->lastName('Wahabali');
        $expected = [
            'chat_id' => 12345,
            'phone_number' => '00000000',
            'first_name' => 'Faissal',
            'last_name' => 'Wahabali',
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}
