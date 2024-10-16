<?php

use NotificationChannels\Telegram\TelegramLocation;
use NotificationChannels\Telegram\Tests\TestSupport\TestLocationNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;

const TEST_LAT = 38.8951;
const TEST_LONG = -77.0364;

it('accepts content when constructed', function () {
    $message = new TelegramLocation(TEST_LAT, TEST_LONG);
    expect($message->getPayloadValue('latitude'))
        ->toEqual(TEST_LAT)
        ->and($message->getPayloadValue('longitude'))
        ->toEqual(TEST_LONG);
});

it('accepts content when created', function () {
    $message = TelegramLocation::create(TEST_LAT, TEST_LONG);
    expect($message->getPayloadValue('latitude'))
        ->toEqual(TEST_LAT)
        ->and($message->getPayloadValue('longitude'))
        ->toEqual(TEST_LONG);
});

test('the recipients chat id can be set', function () {
    $message = new TelegramLocation;
    $message->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the notification latitude can be set', function () {
    $message = new TelegramLocation;
    $message->latitude(TEST_LAT);
    expect($message->getPayloadValue('latitude'))->toEqual(TEST_LAT);
});

test('the notification longitude can be set', function () {
    $message = new TelegramLocation;
    $message->longitude(TEST_LONG);
    expect($message->getPayloadValue('longitude'))->toEqual(TEST_LONG);
});

test('additional options can be set for the message', function () {
    $message = new TelegramLocation;
    $message->options(['foo' => 'bar']);
    expect($message->getPayloadValue('foo'))->toEqual('bar');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = new TelegramLocation;
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = new TelegramLocation(TEST_LAT, TEST_LONG);
    $message->to(12345);
    $message->options(['foo' => 'bar']);
    $expected = [
        'chat_id' => 12345,
        'foo' => 'bar',
        'latitude' => TEST_LAT,
        'longitude' => TEST_LONG,
    ];

    expect($message->toArray())->toEqual($expected);
});

it('can send a location', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestLocationNotification(TEST_LAT, TEST_LONG);

    $expectedResponse = $this->makeMockResponse([
        'location' => collect($notification->toTelegram($notifiable)->toArray())->except('chat_id')->toArray(),
    ]);

    $actualResponse = $this->sendMockNotification('sendLocation', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
