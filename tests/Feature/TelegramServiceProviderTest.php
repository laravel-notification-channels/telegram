<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\ChannelManager;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramServiceProvider;
use ReflectionProperty;

function providerGuzzleConfig(Telegram $telegram): array
{
    $client = (new ReflectionProperty(Telegram::class, 'http'))->getValue($telegram);

    return (new ReflectionProperty(HttpClient::class, 'config'))->getValue($client);
}

it('resolves telegram using the preferred config keys', function () {
    config()->set('services.telegram.token', 'preferred-token');
    config()->set('services.telegram.base_uri', 'https://preferred.example');

    $this->app->forgetInstance(Telegram::class);
    (new TelegramServiceProvider($this->app))->register();

    $telegram = $this->app->make(Telegram::class);

    expect($telegram->getToken())
        ->toBe('preferred-token')
        ->and($telegram->getApiBaseUri())
        ->toBe('https://preferred.example');
});

it('falls back to the legacy config keys', function () {
    config()->set('services.telegram.token', null);
    config()->set('services.telegram.base_uri', null);
    config()->set('services.telegram-bot-api.token', 'legacy-token');
    config()->set('services.telegram-bot-api.base_uri', 'https://legacy.example');

    $this->app->forgetInstance(Telegram::class);
    (new TelegramServiceProvider($this->app))->register();

    $telegram = $this->app->make(Telegram::class);

    expect($telegram->getToken())
        ->toBe('legacy-token')
        ->and($telegram->getApiBaseUri())
        ->toBe('https://legacy.example');
});

it('registers the telegram channel with the channel manager', function () {
    $channel = $this->app->make(ChannelManager::class)->driver('telegram');

    expect($channel)->toBeInstanceOf(TelegramChannel::class);
});

it('applies default http timeouts to the resolved client', function () {
    $this->app->forgetInstance(Telegram::class);
    (new TelegramServiceProvider($this->app))->register();

    $config = providerGuzzleConfig($this->app->make(Telegram::class));

    expect($config['timeout'])->toBe(Telegram::DEFAULT_TIMEOUT)
        ->and($config['connect_timeout'])->toBe(Telegram::DEFAULT_CONNECT_TIMEOUT);
});

it('allows overriding http client options via services config', function () {
    config()->set('services.telegram.http', [
        'timeout' => 5,
        'connect_timeout' => 2,
        'proxy' => 'http://localhost:8080',
    ]);

    $this->app->forgetInstance(Telegram::class);
    (new TelegramServiceProvider($this->app))->register();

    $config = providerGuzzleConfig($this->app->make(Telegram::class));

    expect($config['timeout'])->toBe(5)
        ->and($config['connect_timeout'])->toBe(2)
        ->and($config['proxy'])->toBe('http://localhost:8080');
});
