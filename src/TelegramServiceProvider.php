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
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->bind(Telegram::class, static function () {
            return new Telegram(
                config('services.telegram-bot-api.token'),
                app(HttpClient::class),
                config('services.telegram-bot-api.base_uri')
            );
        });

        Notification::resolved(static function (ChannelManager $service) {
            $service->extend('telegram', static function ($app) {
                return $app->make(TelegramChannel::class);
            });
        });
    }
}
