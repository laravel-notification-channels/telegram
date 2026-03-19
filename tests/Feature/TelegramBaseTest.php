<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use Mockery;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramBase;

it('resolves telegram from the container by default', function () {
    $base = new TelegramBase;

    expect($base->telegram)->toBe($this->telegram);
});

it('uses the provided telegram instance when given', function () {
    $telegram = Mockery::mock(Telegram::class);

    $base = new TelegramBase($telegram);

    expect($base->telegram)->toBe($telegram);
});
