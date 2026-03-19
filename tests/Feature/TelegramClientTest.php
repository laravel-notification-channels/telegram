<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use Mockery;
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

it('sends dice requests through the correct endpoint', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendDice', [
            'form_params' => [
                'chat_id' => 12345,
                'emoji' => '🎯',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendDice([
        'chat_id' => 12345,
        'emoji' => '🎯',
    ]))->toBeInstanceOf(Response::class);
});

it('sends media groups with multipart payloads when requested', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendMediaGroup', [
            'multipart' => [
                [
                    'name' => 'media',
                    'contents' => '[{"type":"photo","media":"attach://file0"}]',
                ],
                [
                    'name' => 'file0',
                    'contents' => 'binary',
                    'filename' => 'photo.jpg',
                ],
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMediaGroup([
        [
            'name' => 'media',
            'contents' => '[{"type":"photo","media":"attach://file0"}]',
        ],
        [
            'name' => 'file0',
            'contents' => 'binary',
            'filename' => 'photo.jpg',
        ],
    ], true))->toBeInstanceOf(Response::class);
});

it('supports edit methods and message deletion endpoints', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/editMessageText', [
            'form_params' => [
                'chat_id' => 12345,
                'message_id' => 1,
                'text' => 'Updated',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/editMessageCaption', [
            'form_params' => [
                'chat_id' => 12345,
                'message_id' => 1,
                'caption' => 'Updated caption',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/editMessageReplyMarkup', [
            'form_params' => [
                'chat_id' => 12345,
                'message_id' => 1,
                'reply_markup' => '{}',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/stopPoll', [
            'form_params' => [
                'chat_id' => 12345,
                'message_id' => 1,
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/deleteMessage', [
            'form_params' => [
                'chat_id' => 12345,
                'message_id' => 1,
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/deleteMessages', [
            'form_params' => [
                'chat_id' => 12345,
                'message_ids' => [1, 2],
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->editMessageText([
        'chat_id' => 12345,
        'message_id' => 1,
        'text' => 'Updated',
    ]))->toBeInstanceOf(Response::class)
        ->and($telegram->editMessageCaption([
            'chat_id' => 12345,
            'message_id' => 1,
            'caption' => 'Updated caption',
        ]))->toBeInstanceOf(Response::class)
        ->and($telegram->editMessageReplyMarkup([
            'chat_id' => 12345,
            'message_id' => 1,
            'reply_markup' => '{}',
        ]))->toBeInstanceOf(Response::class)
        ->and($telegram->stopPoll([
            'chat_id' => 12345,
            'message_id' => 1,
        ]))->toBeInstanceOf(Response::class)
        ->and($telegram->deleteMessage([
            'chat_id' => 12345,
            'message_id' => 1,
        ]))->toBeInstanceOf(Response::class)
        ->and($telegram->deleteMessages([
            'chat_id' => 12345,
            'message_ids' => [1, 2],
        ]))->toBeInstanceOf(Response::class);
});

it('supports chat action and media editing endpoints', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendChatAction', [
            'form_params' => [
                'chat_id' => 12345,
                'action' => 'typing',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/editMessageMedia', [
            'multipart' => [
                [
                    'name' => 'media',
                    'contents' => '{"type":"photo","media":"attach://file0"}',
                ],
                [
                    'name' => 'file0',
                    'contents' => 'binary',
                    'filename' => 'photo.jpg',
                ],
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendChatAction([
        'chat_id' => 12345,
        'action' => 'typing',
    ]))->toBeInstanceOf(Response::class)
        ->and($telegram->editMessageMedia([
            [
                'name' => 'media',
                'contents' => '{"type":"photo","media":"attach://file0"}',
            ],
            [
                'name' => 'file0',
                'contents' => 'binary',
                'filename' => 'photo.jpg',
            ],
        ], true))->toBeInstanceOf(Response::class);
});
