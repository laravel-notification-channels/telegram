<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class TelegramMessageTest.
 *
 * @internal
 * @coversNothing
 */
class TelegramMessageTest extends TestCase
{
    /** @test */
    public function itAcceptsContentWhenConstructed(): void
    {
        $message = new TelegramMessage('Laravel Notification Channels are awesome!');
        $this->assertEquals('Laravel Notification Channels are awesome!', $message->getPayloadValue('text'));
    }

    /** @test */
    public function itAcceptsHtmlWhenConstructed(): void
    {
        $message = new TelegramMessage('Laravel Notification Channels are awesome!');
        $message->htmlParseMode();
        $this->assertEquals('Laravel Notification Channels are awesome!', $message->getPayloadValue('text'));
        $this->assertEquals('HTML', $message->getPayloadValue('parse_mode'));
    }

    /** @test */
    public function theDefaultParseModeIsMarkdown(): void
    {
        $message = new TelegramMessage();
        $this->assertEquals('Markdown', $message->getPayloadValue('parse_mode'));
    }

    /** @test */
    public function theAddedParseModeIsHtml(): void
    {
        $message = new TelegramMessage();
        $message->text('Laravel Notification Channels are awesome');
        $this->assertEquals('HTML', $message->getPayloadValue('parse_mode'));
    }

    /** @test */
    public function theRecipientsChatIdCanBeSet(): void
    {
        $message = new TelegramMessage();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function theNotificationMessageCanBeSet(): void
    {
        $message = new TelegramMessage();
        $message->content('Laravel Notification Channels are awesome!');
        $this->assertEquals('Laravel Notification Channels are awesome!', $message->getPayloadValue('text'));
    }

    /** @test */
    public function theNotificationTextCanBeSet(): void
    {
        $message = new TelegramMessage();
        $message->text('Laravel Notification Channels are awesome!');
        $this->assertEquals('Laravel Notification Channels are awesome!', $message->getPayloadValue('text'));
    }

    /** @test */
    public function anInlineButtonCanBeAddedToTheMessage(): void
    {
        $message = new TelegramMessage();
        $message->button('Laravel', 'https://laravel.com');
        $this->assertEquals(
            '{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"}]]}',
            $message->getPayloadValue('reply_markup')
        );
    }

    /** @test */
    public function anInlineButtonWithCallbackCanBeAddedToTheMessage(): void
    {
        $message = new TelegramMessage();
        $message->buttonWithCallback('Laravel', 'laravel_callback');
        $this->assertEquals(
            '{"inline_keyboard":[[{"text":"Laravel","callback_data":"laravel_callback"}]]}',
            $message->getPayloadValue('reply_markup')
        );
    }

    /** @test */
    public function additionalOptionsCanBeSetForTheMessage(): void
    {
        $message = new TelegramMessage();
        $message->options(['foo' => 'bar']);
        $this->assertEquals('bar', $message->getPayloadValue('foo'));
    }

    /** @test */
    public function itCanDetermineIfTheRecipientChatIdHasNotBeenSet(): void
    {
        $message = new TelegramMessage();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function itCanReturnThePayloadAsAnArray(): void
    {
        $message = new TelegramMessage('Laravel Notification Channels are awesome!');
        $message->to(12345);
        $message->options(['foo' => 'bar']);
        $message->button('Laravel', 'https://laravel.com');
        $expected = [
            'text' => 'Laravel Notification Channels are awesome!',
            'parse_mode' => 'Markdown',
            'chat_id' => 12345,
            'foo' => 'bar',
            'reply_markup' => '{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"}]]}',
        ];

        $this->assertEquals($expected, $message->toArray());
    }

    /** @test */
    public function testLaravelConditionableTrait(): void
    {
        $message = new TelegramMessage();
        $message->button('Laravel', 'https://laravel.com');
        $message->when(true, fn ($tg) => $tg->button('Github', 'https://github.com'));

        $this->assertEquals(
            '{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"},{"text":"Github","url":"https:\/\/github.com"}]]}',
            $message->getPayloadValue('reply_markup')
        );

        $message->when(false, fn ($tg) => $tg->button('Google', 'https://google.com'));

        $this->assertEquals(
            '{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"},{"text":"Github","url":"https:\/\/github.com"}]]}',
            $message->getPayloadValue('reply_markup')
        );
    }
}
