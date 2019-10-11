<?php

namespace NotificationChannels\Telegram\Test;

use Mockery;
use Orchestra\Testbench\TestCase;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

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

    /** @test */
    public function it_can_send_a_message(): void
    {
        $this->telegram->shouldReceive('sendMessage')->once()->with([
            'text'       => 'Laravel Notification Channels are awesome!',
            'parse_mode' => 'Markdown',
            'chat_id'    => 12345,
        ]);
        $this->channel->send(new TestNotifiable(), new TestNotification());
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
