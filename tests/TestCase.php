<?php

namespace NotificationChannels\Telegram\Tests;

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

    protected function getPackageProviders($app): array
    {
        return [
            TelegramServiceProvider::class,
        ];
    }
}
