<?php

namespace NotificationChannels\Telegram\Test;

use Mockery;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramMessage;
use Orchestra\Testbench\TestCase;

class ChannelTest extends TestCase
{
    /** @var Mockery\Mock */
    protected $telegram;

    /** @var \NotificationChannels\Telegram\TelegramChannel */
    protected $channel;

   public function setUp()
    {
        parent::setUp();
        $this->telegram = Mockery::mock(Telegram::class);
        $this->channel = new TelegramChannel($this->telegram);
    }

    /** @test */
    public function it_can_send_a_message()
    {
        $this->telegram->shouldReceive('sendMessage')->once()->with([
            'text'       => 'Laravel Notification Channels are awesome!',
            'parse_mode' => 'Markdown',
            'chat_id'    => 12345,
        ]);
        $this->channel->send(new TestNotifiable(), new TestNotification());
    }

    /** @test */
    public function it_throws_an_exception_when_it_could_not_send_the_notification_because_no_chat_id_provided()
    {
        $this->setExpectedException(CouldNotSendNotification::class);
        $this->channel->send(new TestNotifiable(), new TestNotificationNoChatId());
    }

}

class TestNotifiable
{
    use \Illuminate\Notifications\Notifiable;
    /**
     * @return int
     */
    public function routeNotificationForTelegram()
    {
        return false;
    }
}

class TestNotification extends Notification
{
    public function toTelegram($notifiable)
    {
        return TelegramMessage::create('Laravel Notification Channels are awesome!')->to(12345);
    }
}

class TestNotificationNoChatId extends Notification
{
    public function toTelegram($notifiable)
    {
        return TelegramMessage::create();
    }
}