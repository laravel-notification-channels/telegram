<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use NotificationChannels\Telegram\Facades\Telegram as TelegramFacade;
use NotificationChannels\Telegram\Telegram;

it('resolves the telegram client through the facade', function () {
    expect(TelegramFacade::getFacadeRoot())->toBeInstanceOf(Telegram::class);
});

it('proxies calls to the underlying client', function () {
    $this->telegram
        ->shouldReceive('getApiBaseUri')
        ->once()
        ->andReturn('https://api.telegram.org');

    expect(TelegramFacade::getApiBaseUri())->toBe('https://api.telegram.org');
});
