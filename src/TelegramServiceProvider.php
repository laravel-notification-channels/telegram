<?php

namespace NotificationChannels\Telegram;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

/**
 * Class TelegramServiceProvider.
 */
class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->app->when(TelegramChannel::class)
            ->needs(Telegram::class)
            ->give(static function () {
                return new Telegram(
                    config('services.telegram-bot-api.token'),
                    app(HttpClient::class),
                    config('services.telegram-bot-api.base_uri')
                );
            });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        Notification::resolved(static function (ChannelManager $service) {
            $service->extend('telegram', static function ($app) {
                return $app->make(TelegramChannel::class);
            });
        });
    }
}
