<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramServiceProvider;

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
