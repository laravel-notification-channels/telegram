<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JsonException;
use Mockery;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Telegram;
use ReflectionProperty;
use RuntimeException;

function telegramGuzzleConfig(Telegram $telegram): array
{
    $client = (new ReflectionProperty(Telegram::class, 'http'))->getValue($telegram);

    return (new ReflectionProperty(HttpClient::class, 'config'))->getValue($client);
}

function telegramTooManyRequestsException(int $retryAfter): ClientException
{
    return new ClientException(
        'Too Many Requests',
        new Request('POST', 'https://api.telegram.org/bottoken/sendMessage'),
        new Response(429, [], json_encode([
            'ok' => false,
            'error_code' => 429,
            'description' => 'Too Many Requests: retry after '.$retryAfter,
            'parameters' => ['retry_after' => $retryAfter],
        ]))
    );
}

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

it('throws a json exception when response json is invalid', function () {
    $response = new Response(200, [], '{invalid json');

    Telegram::decodeResponse($response);
})->throws(JsonException::class);

it('uses the default api base uri when none is provided', function () {
    $telegram = new Telegram;

    expect($telegram->getApiBaseUri())->toBe('https://api.telegram.org');
});

it('trims trailing slashes from the api base uri', function () {
    $telegram = new Telegram(apiBaseUri: 'https://example.com///');

    expect($telegram->getApiBaseUri())->toBe('https://example.com');
});

it('can set token and swap the http client', function () {
    $http = Mockery::mock(HttpClient::class);
    $telegram = new Telegram;

    expect($telegram->setToken('new-token'))
        ->toBe($telegram)
        ->and($telegram->getToken())
        ->toBe('new-token')
        ->and($telegram->setHttpClient($http))
        ->toBe($telegram);
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

it('sends rich messages through the correct endpoints', function (string $method, string $endpoint) {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/'.$endpoint, [
            'form_params' => [
                'chat_id' => 12345,
                'rich_message' => '{"blocks":[{"type":"divider"}]}',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->{$method}([
        'chat_id' => 12345,
        'rich_message' => '{"blocks":[{"type":"divider"}]}',
    ]))->toBeInstanceOf(Response::class);
})->with([
    ['sendRichMessage', 'sendRichMessage'],
    ['sendRichMessageDraft', 'sendRichMessageDraft'],
]);

it('resolves the send endpoint from the file type', function (string $type, string $endpoint) {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/'.$endpoint, [
            'form_params' => [
                'chat_id' => 12345,
                $type => 'https://example.com/file',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendFile([
        'chat_id' => 12345,
        $type => 'https://example.com/file',
    ], $type))->toBeInstanceOf(Response::class);
})->with([
    ['photo', 'sendPhoto'],
    ['video_note', 'sendVideoNote'],
    ['live_photo', 'sendLivePhoto'],
]);

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
                'message_ids' => '[1,2]',
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

it('json encodes array params for form requests', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendMessage', [
            'form_params' => [
                'chat_id' => 12345,
                'text' => 'Hello',
                'reply_parameters' => '{"message_id":99}',
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
        'reply_parameters' => ['message_id' => 99],
    ]))->toBeInstanceOf(Response::class);
});

it('sends requests through the remaining client endpoints', function (string $method, string $endpoint) {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/'.$endpoint, [
            'form_params' => [
                'chat_id' => 12345,
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->{$method}(['chat_id' => 12345]))->toBeInstanceOf(Response::class);
})->with([
    ['sendPoll', 'sendPoll'],
    ['sendContact', 'sendContact'],
    ['getUpdates', 'getUpdates'],
    ['sendLocation', 'sendLocation'],
    ['sendVenue', 'sendVenue'],
]);

it('defaults the retry delay when the 429 response has no parameters', function () {
    $calls = 0;
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->twice()
        ->andReturnUsing(function () use (&$calls) {
            if (++$calls === 1) {
                throw new ClientException(
                    'Too Many Requests',
                    new Request('POST', 'https://api.telegram.org/bottoken/sendMessage'),
                    new Response(429, [], json_encode(['ok' => false]))
                );
            }

            return new Response(200, [], json_encode(['ok' => true]));
        });

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]))->toBeInstanceOf(Response::class);
});

it('defaults the retry delay when retry_after is malformed', function () {
    $calls = 0;
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->twice()
        ->andReturnUsing(function () use (&$calls) {
            if (++$calls === 1) {
                throw new ClientException(
                    'Too Many Requests',
                    new Request('POST', 'https://api.telegram.org/bottoken/sendMessage'),
                    new Response(429, [], json_encode([
                        'ok' => false,
                        'parameters' => ['retry_after' => 'soon'],
                    ]))
                );
            }

            return new Response(200, [], json_encode(['ok' => true]));
        });

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]))->toBeInstanceOf(Response::class);
});

it('normalizes plain key value pairs into multipart items', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendMediaGroup', [
            'multipart' => [
                [
                    'name' => 'chat_id',
                    'contents' => 12345,
                ],
                [
                    'name' => 'media',
                    'contents' => '[{"type":"photo","media":"attach://file0"}]',
                ],
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMediaGroup([
        'chat_id' => 12345,
        [
            'name' => 'media',
            'contents' => '[{"type":"photo","media":"attach://file0"}]',
        ],
    ], true))->toBeInstanceOf(Response::class);
});

it('throws when the bot token is missing', function () {
    $telegram = new Telegram;

    $telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]);
})->throws(CouldNotSendNotification::class, 'You must provide your telegram bot token to make any API requests.');

it('wraps client exceptions from telegram', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->andThrow(new ClientException(
            'Bad Request',
            new Request('POST', 'https://api.telegram.org/bottoken/sendMessage'),
            new Response(400, [], json_encode([
                'ok' => false,
                'description' => 'chat not found',
            ]))
        ));

    $telegram = new Telegram('token', $http);

    $telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]);
})->throws(CouldNotSendNotification::class, 'Telegram responded with an error `400 - chat not found`');

it('configures sane default http timeouts', function () {
    $config = telegramGuzzleConfig(new Telegram);

    expect($config['timeout'])->toBe(Telegram::DEFAULT_TIMEOUT)
        ->and($config['connect_timeout'])->toBe(Telegram::DEFAULT_CONNECT_TIMEOUT);
});

it('wraps server exceptions from telegram', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->andThrow(new ServerException(
            'Bad Gateway',
            new Request('POST', 'https://api.telegram.org/bottoken/sendMessage'),
            new Response(502, [], json_encode([
                'ok' => false,
                'description' => 'Bad Gateway',
            ]))
        ));

    $telegram = new Telegram('token', $http);

    $telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]);
})->throws(CouldNotSendNotification::class, 'Telegram responded with an error `502 - Bad Gateway`');

it('retries the request when telegram responds with 429', function () {
    $calls = 0;
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->twice()
        ->andReturnUsing(function () use (&$calls) {
            if (++$calls === 1) {
                throw telegramTooManyRequestsException(0);
            }

            return new Response(200, [], json_encode(['ok' => true]));
        });

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]))->toBeInstanceOf(Response::class);
});

it('throws when telegram keeps responding with 429', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->twice()
        ->andReturnUsing(fn () => throw telegramTooManyRequestsException(0));

    $telegram = new Telegram('token', $http);

    $telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]);
})->throws(CouldNotSendNotification::class, 'Telegram responded with an error `429 - Too Many Requests: retry after 0`');

it('preserves null form params', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendMessage', [
            'form_params' => [
                'chat_id' => 12345,
                'text' => 'Hello',
                'reply_markup' => null,
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
        'reply_markup' => null,
    ]))->toBeInstanceOf(Response::class);
});

it('wraps array values missing a multipart name into multipart items', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendMediaGroup', [
            'multipart' => [
                [
                    'name' => 'options',
                    'contents' => ['contents' => 'raw'],
                ],
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMediaGroup([
        'options' => ['contents' => 'raw'],
    ], true))->toBeInstanceOf(Response::class);
});

it('wraps array values without a multipart shape into multipart items', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->with('https://api.telegram.org/bottoken/sendMediaGroup', [
            'multipart' => [
                [
                    'name' => 'options',
                    'contents' => ['foo' => 'bar'],
                ],
            ],
        ])
        ->andReturn(new Response(200, [], json_encode(['ok' => true])));

    $telegram = new Telegram('token', $http);

    expect($telegram->sendMediaGroup([
        'options' => ['foo' => 'bar'],
    ], true))->toBeInstanceOf(Response::class);
});

it('does not retry when retry_after exceeds the wait cap', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->andReturnUsing(fn () => throw telegramTooManyRequestsException(3600));

    $telegram = new Telegram('token', $http);

    $telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]);
})->throws(CouldNotSendNotification::class, 'Telegram responded with an error `429');

it('wraps generic communication exceptions', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('post')
        ->once()
        ->andThrow(new RuntimeException('Connection refused'));

    $telegram = new Telegram('token', $http);

    $telegram->sendMessage([
        'chat_id' => 12345,
        'text' => 'Hello',
    ]);
})->throws(CouldNotSendNotification::class, 'The communication with Telegram failed. `Connection refused`');
