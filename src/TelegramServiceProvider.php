<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Telegram::class, function (Application $app): Telegram {
            /** @var string|null $token */
            $token = config('services.telegram.token')
                ?? config('services.telegram-bot-api.token');
            /** @var string|null $baseUri */
            $baseUri = config('services.telegram.base_uri')
                ?? config('services.telegram-bot-api.base_uri');
            /** @var array<string, mixed> $httpOptions */
            $httpOptions = config('services.telegram.http') ?? [];

            /** @var HttpClient $httpClient */
            $httpClient = $app->make(HttpClient::class, [
                'config' => $httpOptions + [
                    'timeout' => Telegram::DEFAULT_TIMEOUT,
                    'connect_timeout' => Telegram::DEFAULT_CONNECT_TIMEOUT,
                ],
            ]);

            return new Telegram($token, $httpClient, $baseUri);
        });

        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('telegram', fn (Application $app) => $app->make(TelegramChannel::class));
        });
    }
}
