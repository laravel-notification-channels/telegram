<?php

use GuzzleHttp\Psr7\Response;
use NotificationChannels\Telegram\TelegramUpdates;

it('can limit number of updates', function () {
    $update = TelegramUpdates::create();
    $update->limit(5);

    $payload = $update->toArray();

    expect($payload)
        ->toHaveKey('limit')
        ->and($payload['limit'])
        ->toBe(5);
});

it('can fetch latest update', function () {
    $update = TelegramUpdates::create();
    $update->latest();

    $payload = $update->toArray();

    expect($payload)
        ->toHaveKey('offset')
        ->and($payload['offset'])
        ->toBe(-1);
});

it('can add additional options', function () {
    $update = TelegramUpdates::create()
        ->limit(5)
        ->options([
            'timeout' => 2,
        ]);

    $payload = $update->toArray();

    expect($payload)
        ->toHaveKeys(['limit', 'timeout'])
        ->toBe([
            'limit' => 5,
            'timeout' => 2,
        ]);
});

it('can return the payload as an array', function () {
    $update = TelegramUpdates::create();
    $update->limit(5);

    $payload = $update->toArray();

    expect($payload)->toBe([
        'limit' => 5,
    ]);
});

it('can get updates', function () {
    $expectedResponse = [
        'ok' => true,
        'result' => [
            [
                'update_id' => 123456789,
                'message' => [
                    'message_id' => 1,
                    'from' => [
                        'id' => 987654321,
                        'is_bot' => false,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'username' => 'johndoe',
                        'language_code' => 'en',
                    ],
                    'chat' => [
                        'id' => 987654321,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'username' => 'johndoe',
                        'type' => 'private',
                    ],
                    'date' => 1600000000,
                    'text' => 'Hello World',
                ],
            ],
        ],
    ];

    $update = TelegramUpdates::create()
        ->limit(1)
        ->options([
            'timeout' => 2,
        ]);

    $this->telegram
        ->shouldReceive('getUpdates')
        ->with($update->toArray())
        ->once()
        ->andReturns(new Response(200, [], json_encode($expectedResponse)));

    $actualResponse = $update->get();

    expect($actualResponse)->toBe($expectedResponse);
});
