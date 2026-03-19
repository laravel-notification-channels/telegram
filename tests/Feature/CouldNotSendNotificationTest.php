<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Exception\ClientException;
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
