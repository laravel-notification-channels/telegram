<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use Closure;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\TelegramBase;

final class TestCallableHandler
{
    public function __invoke(array $data): string
    {
        return 'handled-'.$data['to'];
    }
}

it('can unset parse mode and serialize the payload', function () {
    $message = new TelegramBase;
    $message->parseMode(ParseMode::HTML)
        ->to(12345)
        ->normal();

    expect($message->toArray())
        ->toBe(['chat_id' => 12345])
        ->and($message->jsonSerialize())
        ->toBe(['chat_id' => 12345])
        ->and($message->getPayloadValue('parse_mode'))
        ->toBeNull();
});

it('normalizes invalid keyboard column counts', function () {
    $message = new TelegramBase;
    $message->keyboard('One', 0)
        ->keyboard('Two', 0);

    $replyMarkup = json_decode((string) $message->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR);

    expect($replyMarkup)->toBe([
        'keyboard' => [
            [
                ['text' => 'One', 'request_contact' => false, 'request_location' => false],
            ],
            [
                ['text' => 'Two', 'request_contact' => false, 'request_location' => false],
            ],
        ],
        'one_time_keyboard' => true,
        'resize_keyboard' => true,
    ]);
});

it('normalizes invalid inline button column counts', function () {
    $message = new TelegramBase;
    $message->button('Docs', 'https://example.com/docs', 0)
        ->buttonWithCallback('Confirm', 'confirm', 0)
        ->buttonWithWebApp('Open', 'https://example.com/app', 0);

    $replyMarkup = json_decode((string) $message->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR);

    expect($replyMarkup)->toBe([
        'inline_keyboard' => [
            [
                ['text' => 'Docs', 'url' => 'https://example.com/docs'],
            ],
            [
                ['text' => 'Confirm', 'callback_data' => 'confirm'],
            ],
            [
                ['text' => 'Open', 'web_app' => ['url' => 'https://example.com/app']],
            ],
        ],
    ]);
});

it('supports non closure error handlers and send conditions', function () {
    $message = new TelegramBase;
    $message->onError(new TestCallableHandler)
        ->sendWhen(false);

    expect($message->exceptionHandler)
        ->toBeInstanceOf(Closure::class)
        ->and(($message->exceptionHandler)(['to' => 12345]))
        ->toBe('handled-12345')
        ->and($message->canSend())
        ->toBeFalse();

    $message->sendWhen(fn () => true);

    expect($message->canSend())->toBeTrue();
});

it('reports token state and default sendability', function () {
    $message = new TelegramBase;

    expect($message->hasToken())
        ->toBeFalse()
        ->and($message->canSend())
        ->toBeTrue()
        ->and($message->getPayloadValue('missing'))
        ->toBeNull();
});
