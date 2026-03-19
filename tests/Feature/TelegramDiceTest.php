<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use NotificationChannels\Telegram\TelegramDice;
use NotificationChannels\Telegram\Tests\TestSupport\TestDiceNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;

it('can be instantiated', function () {
    $dice = new TelegramDice('🎲');

    expect($dice->getPayloadValue('emoji'))->toBe('🎲');
});

it('can be created using static method', function () {
    $dice = TelegramDice::create('🎯');

    expect($dice->getPayloadValue('emoji'))->toBe('🎯');
});

it('can set the emoji', function () {
    $dice = TelegramDice::create()->emoji('🎰');

    expect($dice->getPayloadValue('emoji'))->toBe('🎰');
});

it('can return the payload as an array', function () {
    $dice = TelegramDice::create('🎳')->to(12345);

    expect($dice->toArray())->toBe([
        'emoji' => '🎳',
        'chat_id' => 12345,
    ]);
});

it('can send a dice message', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestDiceNotification;

    $expectedResponse = $this->makeMockResponse([
        'dice' => [
            'emoji' => '🎯',
            'value' => 6,
        ],
    ]);

    $actualResponse = $this->sendMockNotification('sendDice', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
