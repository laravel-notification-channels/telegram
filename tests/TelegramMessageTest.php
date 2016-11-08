<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramMessage;

class TelegramMessageTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_accepts_content_when_constructed()
    {
        $message = new TelegramMessage('Laravel Notification Channels are awesome!');
        $this->assertEquals('Laravel Notification Channels are awesome!', $message->payload['text']);
    }

    /** @test */
    public function the_default_parse_mode_is_markdown()
    {
        $message = new TelegramMessage();
        $this->assertEquals('Markdown', $message->payload['parse_mode']);
    }

    /** @test */
    public function the_recipients_chat_id_can_be_set()
    {
        $message = new TelegramMessage();
        $message->to(12345);
        $this->assertEquals(12345, $message->payload['chat_id']);
    }

    /** @test */
    public function the_notification_message_can_be_set()
    {
        $message = new TelegramMessage();
        $message->content('Laravel Notification Channels are awesome!');
        $this->assertEquals('Laravel Notification Channels are awesome!', $message->payload['text']);
    }

    /** @test */
    public function an_inline_button_can_be_added_to_the_messsage()
    {
        $message = new TelegramMessage();
        $message->button('Laravel', 'https://laravel.com');
        $this->assertEquals('{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"}]]}', $message->payload['reply_markup']);
    }

    /** @test */
    public function additional_options_can_be_set_for_the_message()
    {
        $message = new TelegramMessage();
        $message->options(['foo' => 'bar']);
        $this->assertEquals('bar', $message->payload['foo']);
    }

    /** @test */
    public function it_can_determine_if_the_recipient_chat_id_has_not_been_set()
    {
        $message = new TelegramMessage();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

        /** @test */
    public function it_can_return_the_payload_as_an_array()
    {
        $message = new TelegramMessage('Laravel Notification Channels are awesome!');
        $message->to(12345);
        $message->options(['foo' => 'bar']);
        $message->button('Laravel', 'https://laravel.com');
        $expected = [
            "text" => "Laravel Notification Channels are awesome!",
            "parse_mode" => "Markdown",
            "chat_id" => 12345,
            "foo" => "bar",
            "reply_markup" => '{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"}]]}',
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}