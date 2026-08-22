<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

it('builds an exception message from telegram error response description', function () {
    $exception = new ClientException(
        'Bad Request',
        new Request('POST', 'https://api.telegram.org/bot/sendMessage'),
        new Response(400, [], json_encode([
            'ok' => false,
            'description' => 'chat not found',
        ]))
    );

    $wrappedException = CouldNotSendNotification::telegramRespondedWithAnError($exception);

    expect($wrappedException->getMessage())->toBe('Telegram responded with an error `400 - chat not found`');
});

it('falls back when telegram error response has no description', function () {
    $exception = new ClientException(
        'Bad Request',
        new Request('POST', 'https://api.telegram.org/bot/sendMessage'),
        new Response(400, [], json_encode([
            'ok' => false,
        ]))
    );

    $wrappedException = CouldNotSendNotification::telegramRespondedWithAnError($exception);

    expect($wrappedException->getMessage())->toBe('Telegram responded with an error `400 - no description given`');
});

it('falls back when the telegram error response is not valid json', function () {
    $exception = new ClientException(
        'Bad Request',
        new Request('POST', 'https://api.telegram.org/bot/sendMessage'),
        new Response(400, [], '{invalid json')
    );

    $wrappedException = CouldNotSendNotification::telegramRespondedWithAnError($exception);

    expect($wrappedException->getMessage())->toBe('Telegram responded with an error `400 - no description given`');
});

it('accepts server exceptions', function () {
    $exception = new ServerException(
        'Internal Server Error',
        new Request('POST', 'https://api.telegram.org/bot/sendMessage'),
        new Response(500, [], json_encode([
            'ok' => false,
            'description' => 'Internal Server Error',
        ]))
    );

    $wrappedException = CouldNotSendNotification::telegramRespondedWithAnError($exception);

    expect($wrappedException->getMessage())->toBe('Telegram responded with an error `500 - Internal Server Error`');
});

it('builds helper exception messages', function () {
    expect(CouldNotSendNotification::telegramBotTokenNotProvided('Missing token')->getMessage())
        ->toBe('Missing token')
        ->and(CouldNotSendNotification::couldNotCommunicateWithTelegram('Connection refused')->getMessage())
        ->toBe('The communication with Telegram failed. `Connection refused`')
        ->and(CouldNotSendNotification::fileAccessFailed('/tmp/missing.txt')->getMessage())
        ->toBe('Failed to open file: /tmp/missing.txt')
        ->and(CouldNotSendNotification::invalidFileIdentifier('bad-file')->getMessage())
        ->toBe('Invalid file identifier: bad-file');
});
