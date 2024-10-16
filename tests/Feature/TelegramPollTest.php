<?php

use NotificationChannels\Telegram\TelegramPoll;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestPollNotification;

it('accepts question when constructed', function () {
    $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
    expect($message->getPayloadValue('question'))->toEqual("Aren't Laravel Notification Channels awesome?");
});

test('the recipients chat id can be set', function () {
    $message = new TelegramPoll;
    $message->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the question message can be set', function () {
    $message = new TelegramPoll;
    $message->question("Aren't Laravel Notification Channels awesome?");
    expect($message->getPayloadValue('question'))->toEqual("Aren't Laravel Notification Channels awesome?");
});

it('accepts question when created', function () {
    $message = TelegramPoll::create("Aren't Laravel Notification Channels awesome?");
    expect($message->getPayloadValue('question'))->toEqual("Aren't Laravel Notification Channels awesome?");
});

test('the options can be set for the question', function () {
    $message = new TelegramPoll;
    $message->choices(['Yes', 'No']);
    expect($message->getPayloadValue('options'))->toEqual('["Yes","No"]');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = new TelegramPoll;
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
    $message->to(12345);
    $message->choices(['Yes', 'No']);
    $expected = [
        'chat_id' => 12345,
        'question' => "Aren't Laravel Notification Channels awesome?",
        'options' => '["Yes","No"]',
    ];

    expect($message->toArray())->toEqual($expected);
});

it('can send a poll', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestPollNotification;

    $expectedResponse = $this->makeMockResponse([
        'poll' => [
            'id' => '1234567890101112',
            'question' => "Isn't Telegram Notification Channel Awesome?",
            'options' => [
                [
                    'text' => 'Yes',
                    'voter_count' => 0,
                ],
                [
                    'text' => 'No',
                    'voter_count' => 0,
                ],
            ],
            'total_voter_count' => 0,
            'is_closed' => false,
            'is_anonymous' => true,
            'type' => 'regular',
            'allows_multiple_answers' => false,
        ],
    ]);

    $actualResponse = $this->sendMockNotification('sendPoll', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
