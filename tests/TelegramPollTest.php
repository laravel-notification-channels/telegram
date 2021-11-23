<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramPoll;
use PHPUnit\Framework\TestCase;

/**
 * Class TelegramPollTest.
 */
class TelegramPollTest extends TestCase
{
    /** @test */
    public function it_accepts_question_when_constructed(): void
    {
        $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
        $this->assertEquals("Aren't Laravel Notification Channels awesome?", $message->getPayloadValue('question'));
    }

    /** @test */
    public function the_recipients_chat_id_can_be_set(): void
    {
        $message = new TelegramPoll();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function the_question_message_can_be_set(): void
    {
        $message = new TelegramPoll();
        $message->question("Aren't Laravel Notification Channels awesome?");
        $this->assertEquals("Aren't Laravel Notification Channels awesome?", $message->getPayloadValue('question'));
    }

    /** @test */
    public function the_options_can_be_set_for_the_question(): void
    {
        $message = new TelegramPoll();
        $message->options(['Yes', 'No']);
        $this->assertEquals('["Yes","No"]', $message->getPayloadValue('options'));
    }

    /** @test */
    public function it_can_determine_if_the_recipient_chat_id_has_not_been_set(): void
    {
        $message = new TelegramPoll();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function it_can_return_the_payload_as_an_array(): void
    {
        $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
        $message->to(12345);
        $message->options(['Yes', 'No']);
        $expected = [
            'chat_id'   => 12345,
            'question'  => "Aren't Laravel Notification Channels awesome?",
            'options'   => '["Yes","No"]',
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}
