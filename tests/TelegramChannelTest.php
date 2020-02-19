<?php

namespace NotificationChannels\Telegram\Test;

use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Mockery;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use PHPUnit\Framework\TestCase;

/**
 * Class ChannelTest.
 */
class ChannelTest extends TestCase
{
    /** @var Mockery\Mock */
    protected $telegram;

    /** @var TelegramChannel */
    protected $channel;

    public function setUp(): void
    {
        parent::setUp();
        $this->telegram = Mockery::mock(Telegram::class);
        $this->channel = new TelegramChannel($this->telegram);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_send_a_message(): void
    {
        $expectedResponse = ['ok' => true, 'result' => ['message_id' => 123, 'chat' => ['id' => 12345]]];

        $this->telegram->shouldReceive('sendMessage')->once()->with([
            'text' => 'Laravel Notification Channels are awesome!',
            'parse_mode' => 'Markdown',
            'chat_id' => 12345,
        ])
            ->andReturns(new Response(200, [], json_encode($expectedResponse)));

        $actualResponse = $this->channel->send(new TestNotifiable(), new TestNotification());

        self::assertSame($expectedResponse, $actualResponse);
    }

    /** @test */
    public function it_throws_an_exception_when_it_could_not_send_the_notification_because_no_chat_id_provided(): void
    {
        $this->expectException(CouldNotSendNotification::class);
        $this->channel->send(new TestNotifiable(), new TestNotificationNoChatId());
    }
}

/**
 * Class TestNotifiable.
 */
class TestNotifiable
{
    use Notifiable;

    /**
     * @return int
     */
    public function routeNotificationForTelegram(): int
    {
        return false;
    }
}

/**
 * Class TestNotification.
 */
class TestNotification extends Notification
{
    /**
     * @param $notifiable
     *
     * @return TelegramMessage
     */
    public function toTelegram($notifiable): TelegramMessage
    {
        return TelegramMessage::create('Laravel Notification Channels are awesome!')->to(12345);
    }
}

/**
 * Class TestNotificationNoChatId.
 */
class TestNotificationNoChatId extends Notification
{
    /**
     * @param $notifiable
     *
     * @return TelegramMessage
     */
    public function toTelegram($notifiable): TelegramMessage
    {
        return TelegramMessage::create();
    }
}
