<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use NotificationChannels\Telegram\TelegramVenue;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestVenueNotification;

function venue(): array
{
    return [38.8951, -77.0364, 'Grand Palace', 'Bangkok, Thailand'];
}

it('accepts content when constructed', function () {
    [$lat, $long, $title, $address] = venue();

    $message = new TelegramVenue($lat, $long, $title, $address);

    expect($message->getPayloadValue('latitude'))->toEqual($lat)
        ->and($message->getPayloadValue('longitude'))->toEqual($long)
        ->and($message->getPayloadValue('title'))->toEqual($title)
        ->and($message->getPayloadValue('address'))->toEqual($address);
});

it('accepts content when created', function () {
    [$lat, $long, $title, $address] = venue();

    $message = TelegramVenue::create($lat, $long, $title, $address);

    expect($message->getPayloadValue('latitude'))->toEqual($lat)
        ->and($message->getPayloadValue('longitude'))->toEqual($long)
        ->and($message->getPayloadValue('title'))->toEqual($title)
        ->and($message->getPayloadValue('address'))->toEqual($address);
});

test('the recipients chat id can be set', function () {
    $message = new TelegramVenue;
    $message->to(12345);

    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the notification latitude can be set', function () {
    [$lat] = venue();

    $message = new TelegramVenue;
    $message->latitude($lat);

    expect($message->getPayloadValue('latitude'))->toEqual($lat);
});

test('the notification longitude can be set', function () {
    [, $long] = venue();

    $message = new TelegramVenue;
    $message->longitude($long);

    expect($message->getPayloadValue('longitude'))->toEqual($long);
});

test('the venue title can be set', function () {
    [, , $title] = venue();

    $message = new TelegramVenue;
    $message->title($title);

    expect($message->getPayloadValue('title'))->toEqual($title);
});

test('the venue address can be set', function () {
    [, , , $address] = venue();

    $message = new TelegramVenue;
    $message->address($address);

    expect($message->getPayloadValue('address'))->toEqual($address);
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
    [$lat, $long, $title, $address] = venue();

    $message = new TelegramVenue($lat, $long, $title, $address);
    $message->to(12345);
    $message->options(['foo' => 'bar']);

    $expected = [
        'chat_id' => 12345,
        'foo' => 'bar',
        'latitude' => $lat,
        'longitude' => $long,
        'title' => $title,
        'address' => $address,
    ];

    expect($message->toArray())->toEqual($expected);
});

it('can send a venue', function () {
    [$lat, $long, $title, $address] = venue();

    $notifiable = new TestNotifiable;
    $notification = new TestVenueNotification($lat, $long, $title, $address);

    $expectedResponse = $this->makeMockResponse([
        'venue' => collect($notification->toTelegram($notifiable)->toArray())
            ->except('chat_id')
            ->toArray(),
    ]);

    $actualResponse = $this->sendMockNotification(
        'sendVenue',
        $notifiable,
        $notification,
        $expectedResponse
    );

    expect($actualResponse)->toBe($expectedResponse);
});
