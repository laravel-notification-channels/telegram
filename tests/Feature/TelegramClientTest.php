<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use NotificationChannels\Telegram\Telegram;

it('decodes valid telegram responses', function () {
    $response = new Response(200, [], json_encode([
        'ok' => true,
        'result' => ['message_id' => 123],
    ]));

    expect(Telegram::decodeResponse($response))->toBe([
        'ok' => true,
        'result' => ['message_id' => 123],
    ]);
});

it('throws a guzzle exception when response json is invalid', function () {
    $response = new Response(200, [], '{invalid json');

    Telegram::decodeResponse($response);
})->throws(InvalidArgumentException::class);

it('uses the default api base uri when none is provided', function () {
    $telegram = new Telegram;

    expect($telegram->getApiBaseUri())->toBe('https://api.telegram.org');
});

it('trims trailing slashes from the api base uri', function () {
    $telegram = new Telegram(apiBaseUri: 'https://example.com///');

    expect($telegram->getApiBaseUri())->toBe('https://example.com');
});
