<?php

namespace NotificationChannels\Telegram\Test;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
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
class TelegramChannelTest extends TestCase
{
    /** @var Mockery\Mock */
    protected $telegram;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var TelegramChannel */
    protected $channel;

    public function setUp(): void
    {
        parent::setUp();
        $this->telegram = Mockery::mock(Telegram::class);
        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->channel = new TelegramChannel($this->telegram, $this->dispatcher);
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

    /**
     * @test
     */
    public function notification_failed_event(): void
    {
        self::expectException($exception_class = CouldNotSendNotification::class);
        self::expectExceptionMessage($exception_message = 'Some exception');

        $notifiable = new TestNotifiable();
        $notification = new TestNotification();

        $this->telegram
            ->shouldReceive('sendMessage')
            ->andThrow($exception_class , $exception_message)
        ;

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                new NotificationFailed(
                    $notifiable,
                    $notification,
                    'telegram',
                    []
                )
            )
        ;

        $this->channel->send($notifiable, $notification);
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
