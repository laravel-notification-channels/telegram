<?php

namespace NotificationChannels\Telegram\Test;

use NotificationChannels\Telegram\TelegramAudio;
use PHPUnit\Framework\TestCase;

/**
 * Class TelegramAudioTest.
 *
 * @internal
 * @coversNothing
 */
class TelegramAudioTest extends TestCase
{
    /** @test */
    public function itAcceptsAudioWhenConstructed(): void
    {
        $message = new TelegramAudio('audio.mp3');
        $this->assertEquals('audio.mp3', $message->getPayloadValue('audio'));
    }

    /** @test */
    public function theDefaultParseModeIsMarkdown(): void
    {
        $message = new TelegramAudio('audio.mp3');
        $this->assertEquals('Markdown', $message->getPayloadValue('parse_mode'));
    }

    /** @test */
    public function theRecipientsChatIdCanBeSet(): void
    {
        $message = new TelegramAudio();
        $message->to(12345);
        $this->assertEquals(12345, $message->getPayloadValue('chat_id'));
    }

    /** @test */
    public function theAudioMessageCanBeSet(): void
    {
        $message = new TelegramAudio();
        $message->audio('audio.mp3');
        $this->assertEquals('audio.mp3', $message->getPayloadValue('audio'));
    }

    /** @test */
    public function theAudioCaptionCanBeSet(): void
    {
        $message = new TelegramAudio();
        $message->caption('audio');
        $this->assertEquals('audio', $message->getPayloadValue('caption'));
    }

    /** @test */
    public function itCanDetermineIfTheRecipientChatIdHasNotBeenSet(): void
    {
        $message = new TelegramAudio();
        $this->assertTrue($message->toNotGiven());

        $message->to(12345);
        $this->assertFalse($message->toNotGiven());
    }

    /** @test */
    public function itCanReturnThePayloadAsAnArray(): void
    {
        $message = new TelegramAudio('audio.mp3');
        $message->to(12345);
        $message->caption('audio');
        $expected = [
            'chat_id' => 12345,
            'audio' => 'audio.mp3',
            'caption' => 'audio',
            'parse_mode' => 'Markdown',
        ];

        $this->assertEquals($expected, $message->toArray());
    }
}
