<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramPoll;
use PHPUnit\Framework\TestCase;

/**
 * Class TelegramPollTest.
 *
 * @internal
 * @coversNothing
 */
class TelegramPollTest extends TestCase
{
    /** @test */
    public function itAcceptsQuestionWhenConstructed(): void
    {
        $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
        $this->assertEquals("Aren't Laravel Notification Channels awesome?", $message->getPayloadValue('question'));
    }

    /** @test */
    public function theRecipientsChatIdCanBeSet(): void
    {
        $message = new TelegramPoll();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function theQuestionMessageCanBeSet(): void
    {
        $message = new TelegramPoll();
        $message->question("Aren't Laravel Notification Channels awesome?");
        $this->assertEquals("Aren't Laravel Notification Channels awesome?", $message->getPayloadValue('question'));
    }

    /** @test */
    public function theOptionsCanBeSetForTheQuestion(): void
    {
        $message = new TelegramPoll();
        $message->choices(['Yes', 'No']);
        $this->assertEquals('["Yes","No"]', $message->getPayloadValue('options'));
    }

    /** @test */
    public function itCanDetermineIfTheRecipientChatIdHasNotBeenSet(): void
    {
        $message = new TelegramPoll();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function itCanReturnThePayloadAsAnArray(): void
    {
        $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
        $message->to(12345);
        $message->choices(['Yes', 'No']);
        $expected = [
            'chat_id' => 12345,
            'question' => "Aren't Laravel Notification Channels awesome?",
            'options' => '["Yes","No"]',
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}
