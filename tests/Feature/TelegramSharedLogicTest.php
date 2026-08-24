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

it('can add an icon custom emoji id to inline buttons', function () {
    $message = new TelegramBase;
    $message->button('Docs', 'https://example.com/docs', 1, 'primary', '5368324170671202286')
        ->buttonWithCallback('Confirm', 'confirm', 1, iconCustomEmojiId: '5368324170671202287')
        ->buttonWithWebApp('Open', 'https://example.com/app', 1, iconCustomEmojiId: '5368324170671202288');

    $replyMarkup = json_decode((string) $message->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR);

    expect($replyMarkup)->toBe([
        'inline_keyboard' => [
            [
                [
                    'text' => 'Docs',
                    'url' => 'https://example.com/docs',
                    'style' => 'primary',
                    'icon_custom_emoji_id' => '5368324170671202286',
                ],
            ],
            [
                [
                    'text' => 'Confirm',
                    'callback_data' => 'confirm',
                    'icon_custom_emoji_id' => '5368324170671202287',
                ],
            ],
            [
                [
                    'text' => 'Open',
                    'web_app' => ['url' => 'https://example.com/app'],
                    'icon_custom_emoji_id' => '5368324170671202288',
                ],
            ],
        ],
    ]);
});

it('omits the icon custom emoji id when it is not given', function () {
    $message = new TelegramBase;
    $message->button('Docs', 'https://example.com/docs', 1, 'danger');

    $replyMarkup = json_decode((string) $message->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR);

    expect($replyMarkup['inline_keyboard'][0][0])->toBe([
        'text' => 'Docs',
        'url' => 'https://example.com/docs',
        'style' => 'danger',
    ]);
});

it('can set ephemeral message parameters', function () {
    $message = new TelegramBase;
    $message->to(12345)
        ->receiverUserId(6789)
        ->callbackQueryId('callback-query-id')
        ->replaceCallbackQueryMessage();

    expect($message->toArray())->toBe([
        'chat_id' => 12345,
        'ephemeral_message_parameters' => '{"receiver_user_id":6789,"callback_query_id":"callback-query-id","replace_callback_query_message":true}',
    ]);
});

it('merges raw ephemeral message parameters', function () {
    $message = new TelegramBase;
    $message->ephemeralMessageParameters(['receiver_user_id' => 6789])
        ->ephemeralMessageParameters(['replace_callback_query_message' => false])
        ->receiverUserId(1234);

    expect($message->toArray())->toBe([
        'ephemeral_message_parameters' => '{"receiver_user_id":1234,"replace_callback_query_message":false}',
    ]);
});

it('can add a disabled inline button', function () {
    $message = new TelegramBase;
    $message->buttonWithCallback('Confirm', 'confirm')
        ->disabledButton('Expired', style: 'danger');

    expect($message->getPayloadValue('reply_markup'))
        ->toBe('{"inline_keyboard":[[{"text":"Confirm","callback_data":"confirm"},{"text":"Expired","disabled":{},"style":"danger"}]]}');
});

it('forces a reply on the inline keyboard markup', function () {
    $message = new TelegramBase;
    $message->button('Docs', 'https://example.com/docs')->forceReply();

    expect($message->getPayloadValue('reply_markup'))
        ->toBe('{"inline_keyboard":[[{"text":"Docs","url":"https:\\/\\/example.com\\/docs"}]],"force_reply":true}');

    $message->button('Blog', 'https://example.com/blog');

    expect(json_decode((string) $message->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR))
        ->toHaveKey('force_reply', true);

    $message->forceReply(false);

    expect(json_decode((string) $message->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR))
        ->toHaveKey('force_reply', false);
});

it('forces a reply on the keyboard markup set before and after the buttons', function () {
    $before = (new TelegramBase)->forceReply()->keyboard('One');

    $after = (new TelegramBase)->keyboard('One')->forceReply();

    expect(json_decode((string) $before->getPayloadValue('reply_markup'), true, 512, JSON_THROW_ON_ERROR))
        ->toHaveKey('force_reply', true)
        ->and($after->getPayloadValue('reply_markup'))
        ->toBe($before->getPayloadValue('reply_markup'));
});

it('does not force a reply on markups without buttons', function () {
    $message = new TelegramBase;
    $message->forceReply()->keyboardMarkup(['remove_keyboard' => true]);

    expect($message->getPayloadValue('reply_markup'))->toBe('{"remove_keyboard":true}');
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

it('keeps closure error handlers as-is', function () {
    $closure = function (array $data): string {
        return 'closure-handled';
    };

    $message = new TelegramBase;
    $message->onError($closure);

    expect($message->exceptionHandler)->toBe($closure);
});

it('declares a static return type on onError for fluent subclass chaining', function () {
    $returnType = (new \ReflectionMethod(TelegramBase::class, 'onError'))->getReturnType();

    expect((string) $returnType)->toBe('static');
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
