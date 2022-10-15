<?php

namespace NotificationChannels\Telegram\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected Telegram $telegram;

    protected mixed $dispatcher;

    protected TelegramChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(Telegram::class, function () {
            return Mockery::mock(Telegram::class);
        });

        $this->telegram = app(Telegram::class);

        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->channel = new TelegramChannel($this->dispatcher);
    }

    protected function sendMockNotification(
        string $shouldReceive,
        mixed $notifiable,
        Notification $notification,
        array $expectedResponse
    ) {
        $this->telegram
            ->shouldReceive($shouldReceive)
            ->with($notification->toTelegram($notifiable)->toArray())
            ->once()
            ->andReturns(new Response(200, [], json_encode($expectedResponse)));

        return $this->channel->send($notifiable, $notification);
    }

    protected function makeMockResponse(array $result)
    {
        return [
            "ok"     => true,
            "result" => [
                "message_id" => 9090,
                "from"       => [
                    "id"         => 12345678,
                    "is_bot"     => true,
                    "first_name" => "MyBot",
                    "username"   => "MyBot",
                ],
                "chat"       => [
                    "id"         => 90909090,
                    "first_name" => "John",
                    "last_name"  => "Doe",
                    "username"   => "testuser",
                    "type"       => "private",
                ],
                "date"       => 1600000000,
                ...$result,
            ],
        ];
    }

    protected function getPackageProviders($app): array
    {
        return [
            TelegramServiceProvider::class,
        ];
    }
}
