<?php

use NotificationChannels\Telegram\TelegramVenue;
use NotificationChannels\Telegram\Tests\TestSupport\TestVenueNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;

const TEST_LAT = 38.8951;
const TEST_LONG = -77.0364;
const TEST_TITLE = 'Grand Palace';
const TEST_ADDRESS = 'Bangkok, Thailand';

it('accepts content when constructed', function () {
    $message = new TelegramVenue(TEST_LAT, TEST_LONG, TEST_TITLE, TEST_ADDRESS);
    expect($message->getPayloadValue('latitude'))->toEqual(TEST_LAT)
        ->and($message->getPayloadValue('longitude'))->toEqual(TEST_LONG)
        ->and($message->getPayloadValue('title'))->toEqual(TEST_TITLE)
        ->and($message->getPayloadValue('address'))->toEqual(TEST_ADDRESS);
});

it('accepts content when created', function () {
    $message = TelegramVenue::create(TEST_LAT, TEST_LONG, TEST_TITLE, TEST_ADDRESS);
    expect($message->getPayloadValue('latitude'))->toEqual(TEST_LAT)
        ->and($message->getPayloadValue('longitude'))->toEqual(TEST_LONG)
        ->and($message->getPayloadValue('title'))->toEqual(TEST_TITLE)
        ->and($message->getPayloadValue('address'))->toEqual(TEST_ADDRESS);
});

test('the recipients chat id can be set', function () {
    $message = new TelegramVenue;
    $message->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the notification latitude can be set', function () {
    $message = new TelegramVenue;
    $message->latitude(TEST_LAT);
    expect($message->getPayloadValue('latitude'))->toEqual(TEST_LAT);
});

test('the notification longitude can be set', function () {
    $message = new TelegramVenue;
    $message->longitude(TEST_LONG);
    expect($message->getPayloadValue('longitude'))->toEqual(TEST_LONG);
});

test('the venue title can be set', function () {
    $message = new TelegramVenue;
    $message->title(TEST_TITLE);
    expect($message->getPayloadValue('title'))->toEqual(TEST_TITLE);
});

test('the venue address can be set', function () {
    $message = new TelegramVenue;
    $message->address(TEST_ADDRESS);
    expect($message->getPayloadValue('address'))->toEqual(TEST_ADDRESS);
});

test('optional foursquare id can be set', function () {
    $message = new TelegramVenue;
    $message->foursquareId('4sq12345');
    expect($message->getPayloadValue('foursquare_id'))->toEqual('4sq12345');
});

test('optional foursquare type can be set', function () {
    $message = new TelegramVenue;
    $message->foursquareType('coffee_shop');
    expect($message->getPayloadValue('foursquare_type'))->toEqual('coffee_shop');
});

test('additional options can be set for the message', function () {
    $message = new TelegramVenue;
    $message->options(['foo' => 'bar']);
    expect($message->getPayloadValue('foo'))->toEqual('bar');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = new TelegramVenue;
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = new TelegramVenue(TEST_LAT, TEST_LONG, TEST_TITLE, TEST_ADDRESS);
    $message->to(12345);
    $message->options(['foo' => 'bar']);

    $expected = [
        'chat_id' => 12345,
        'foo' => 'bar',
        'latitude' => TEST_LAT,
        'longitude' => TEST_LONG,
        'title' => TEST_TITLE,
        'address' => TEST_ADDRESS,
    ];

    expect($message->toArray())->toEqual($expected);
});

it('can send a venue', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestVenueNotification(TEST_LAT, TEST_LONG, TEST_TITLE, TEST_ADDRESS);

    $expectedResponse = $this->makeMockResponse([
        'venue' => collect($notification->toTelegram($notifiable)->toArray())->except('chat_id')->toArray(),
    ]);

    $actualResponse = $this->sendMockNotification('sendVenue', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
