<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
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

it('falls back when telegram returns an error without a response', function () {
    $exception = Mockery::mock(ClientException::class);
    $exception->shouldReceive('hasResponse')->once()->andReturnFalse();

    $wrappedException = CouldNotSendNotification::telegramRespondedWithAnError($exception);

    expect($wrappedException->getMessage())->toBe('Telegram responded with an error but no response body found');
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
