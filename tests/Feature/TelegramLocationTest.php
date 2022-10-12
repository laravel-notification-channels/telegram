<?php

use NotificationChannels\Telegram\TelegramLocation;

const TEST_LONG = -77.0364;
const TEST_LAT = 38.8951;

it('accepts content when constructed', function () {
    $message = new TelegramLocation(TEST_LONG, TEST_LAT);
    expect($message->getPayloadValue('latitude'))
        ->toEqual(TEST_LONG)
        ->and($message->getPayloadValue('longitude'))
        ->toEqual(TEST_LAT);
});

it('accepts content when created', function () {
    $message = TelegramLocation::create(TEST_LONG, TEST_LAT);
    expect($message->getPayloadValue('latitude'))
        ->toEqual(TEST_LONG)
        ->and($message->getPayloadValue('longitude'))
        ->toEqual(TEST_LAT);
});

test('the recipients chat id can be set', function () {
    $message = new TelegramLocation();
    $message->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the notification longitude can be set', function () {
    $message = new TelegramLocation();
    $message->longitude(TEST_LAT);
    expect($message->getPayloadValue('longitude'))->toEqual(TEST_LAT);
});

test('the notification latitude can be set', function () {
    $message = new TelegramLocation();
    $message->latitude(TEST_LONG);
    expect($message->getPayloadValue('latitude'))->toEqual(TEST_LONG);
});

test('additional options can be set for the message', function () {
    $message = new TelegramLocation();
    $message->options(['foo' => 'bar']);
    expect($message->getPayloadValue('foo'))->toEqual('bar');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = new TelegramLocation();
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = new TelegramLocation(TEST_LONG, TEST_LAT);
    $message->to(12345);
    $message->options(['foo' => 'bar']);
    $expected = [
        'chat_id' => 12345,
        'foo' => 'bar',
        'longitude' => TEST_LAT,
        'latitude' => TEST_LONG,
    ];

    expect($message->toArray())->toEqual($expected);
});
