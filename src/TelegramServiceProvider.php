<?php

namespace NotificationChannels\Telegram;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->when(Channel::class)
            ->needs(Telegram::class)
            ->give(function () {
                return new Telegram(config('services.telegram-bot-api.token'), new HttpClient());
            });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }
}
