<?php

use NotificationChannels\Telegram\TelegramContact;
use NotificationChannels\Telegram\Tests\TestSupport\TestContactNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;

it('accepts phone number when constructed', function () {
    $message = new TelegramContact('00000000');
    expect($message->getPayloadValue('phone_number'))->toEqual('00000000');
});

it('accepts phone number when created', function () {
    $message = TelegramContact::create('00000000');
    expect($message->getPayloadValue('phone_number'))->toEqual('00000000');
});

test('the recipients chat id can be set', function () {
    $message = new TelegramContact;
    $message->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the phone number can be set', function () {
    $message = new TelegramContact;
    $message->phoneNumber('00000000');
    expect($message->getPayloadValue('phone_number'))->toEqual('00000000');
});

test('the first name can be set for the contact', function () {
    $message = new TelegramContact;
    $message->firstName('John');
    expect($message->getPayloadValue('first_name'))->toEqual('John');
});

test('the last name can be set for the contact', function () {
    $message = new TelegramContact;
    $message->lastName('Doe');
    expect($message->getPayloadValue('last_name'))->toEqual('Doe');
});

test('the card can be set for the contact', function () {
    $message = new TelegramContact;
    $message->vCard('vCard');
    expect($message->getPayloadValue('vcard'))->toEqual('vCard');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = new TelegramContact;
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = new TelegramContact('00000000');
    $message->to(12345);
    $message->firstName('John');
    $message->lastName('Doe');
    $message->vCard('vCard');
    $expected = [
        'chat_id' => 12345,
        'phone_number' => '00000000',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'vcard' => 'vCard',
    ];

    expect($message->toArray())->toEqual($expected);
});

it('can send a contact', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestContactNotification;

    $expectedResponse = $this->makeMockResponse([
        'contact' => collect($notification->toTelegram($notifiable)->toArray())->except('chat_id')->toArray(),
    ]);

    $actualResponse = $this->sendMockNotification('sendContact', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
