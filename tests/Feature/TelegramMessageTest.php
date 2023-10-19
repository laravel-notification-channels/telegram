<?php

use Illuminate\Support\Facades\View;
use NotificationChannels\Telegram\TelegramMessage;

it('accepts content when constructed', function () {
    $message = new TelegramMessage('Laravel Notification Channels are awesome!');
    expect($message->getPayloadValue('text'))->toEqual('Laravel Notification Channels are awesome!');
});

test('the default parse mode is markdown', function () {
    $message = TelegramMessage::create();
    expect($message->getPayloadValue('parse_mode'))->toEqual('Markdown');
});

it('can disable notification', function () {
    $message = TelegramMessage::create()->disableNotification();
    expect($message->getPayloadValue('disable_notification'))->toBeTrue();
});

it('can add one message per line', function () {
    $message = TelegramMessage::create()
        ->line('Laravel Notification Channels are awesome!')
        ->line('Telegram Notification Channel is fantastic :)');
    expect($message->getPayloadValue('text'))->toEqual("Laravel Notification Channels are awesome!\nTelegram Notification Channel is fantastic :)\n");
});

it('can escape special markdown characters per line', function () {
    $message = TelegramMessage::create()
        ->escapedLine('Laravel Notification_Channels are awesome!')
        ->line('Telegram Notification Channel is fantastic :)');

    expect($message->getPayloadValue('text'))->toEqual("Laravel Notification\_Channels are awesome\!\nTelegram Notification Channel is fantastic :)\n");
});

it('can attach a view as the content', function () {
    View::addLocation(__DIR__.'/../TestSupport');

    $message = TelegramMessage::create()->view('TestViewFile', ['name' => 'Telegram Notification Channel']);
    expect($message->getPayloadValue('text'))->toEqual("<h1>Hello, Telegram Notification Channel</h1>\n");
});

it('can chunk a long message into given size', function () {
    $message = TelegramMessage::create();
    $message->content('Laravel Notification Channels are awesome, Telegram Notification Channel is fantastic :)');

    $message->chunk(20);
    expect($message->shouldChunk())->toBeTrue();

    $message->chunk(0);
    expect($message->shouldChunk())->toBeFalse();

    $message->content('Laravel Notification Channels are awesome, Telegram Notification Channel is fantastic :)', 10);
    expect($message->shouldChunk())
        ->toBeTrue()
        ->and($message->chunkSize)
        ->toEqual(10);
});

test('the recipients chat id can be set', function () {
    $message = TelegramMessage::create()->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the notification message can be set', function () {
    $message = TelegramMessage::create()->content('Laravel Notification Channels are awesome!');
    expect($message->getPayloadValue('text'))->toEqual('Laravel Notification Channels are awesome!');
});

test('an inline button can be added to the message', function () {
    $message = TelegramMessage::create()->button('Laravel', 'https://laravel.com');
    expect($message->getPayloadValue('reply_markup'))->toEqual('{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"}]]}');
});

test('an inline button with callback can be added to the message', function () {
    $message = TelegramMessage::create()->buttonWithCallback('Laravel', 'laravel_callback');
    expect($message->getPayloadValue('reply_markup'))->toEqual('{"inline_keyboard":[[{"text":"Laravel","callback_data":"laravel_callback"}]]}');
});

test('additional options can be set for the message', function () {
    $message = TelegramMessage::create()->options(['foo' => 'bar']);
    expect($message->getPayloadValue('foo'))->toEqual('bar');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = TelegramMessage::create();
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = TelegramMessage::create()
        ->content('Laravel Notification Channels are awesome!')
        ->to(12345)
        ->options(['foo' => 'bar'])
        ->button('Laravel', 'https://laravel.com');

    $expected = [
        'text' => 'Laravel Notification Channels are awesome!',
        'parse_mode' => 'Markdown',
        'chat_id' => 12345,
        'foo' => 'bar',
        'reply_markup' => '{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"}]]}',
    ];

    expect($message->toArray())->toEqual($expected);
});

test('laravel conditionable trait', function () {
    $message = TelegramMessage::create()
        ->button('Laravel', 'https://laravel.com')
        ->when(true, fn ($tg) => $tg->button('Github', 'https://github.com'));

    expect($message->getPayloadValue('reply_markup'))->toEqual('{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"},{"text":"Github","url":"https:\/\/github.com"}]]}');

    $message->when(false, fn ($tg) => $tg->button('Google', 'https://google.com'));

    expect($message->getPayloadValue('reply_markup'))->toEqual('{"inline_keyboard":[[{"text":"Laravel","url":"https:\/\/laravel.com"},{"text":"Github","url":"https:\/\/github.com"}]]}');
});

it('can set token', function () {
    $message = TelegramMessage::create()->token('12345');

    expect($message->hasToken())
        ->toBeTrue()
        ->and($message->token)
        ->toEqual('12345');
});

it('can set the parse mode', function () {
    $message = TelegramMessage::create()->options(['parse_mode' => 'HTML']);
    expect($message->getPayloadValue('parse_mode'))->toEqual('HTML');
});

it('can set the disable web page preview', function () {
    $message = TelegramMessage::create()->options(['disable_web_page_preview' => true]);
    expect($message->getPayloadValue('disable_web_page_preview'))->toBeTrue();
});

test('an normal keyboard button can be added to the message', function () {
    $message = TelegramMessage::create()->keyboard('Laravel');
    expect($message->getPayloadValue('reply_markup'))->toEqual(
        '{"keyboard":[[{"text":"Laravel","request_contact":false,"request_location":false}]],"one_time_keyboard":true,"resize_keyboard":true}'
    );
});

test('an request phone keyboard button can be added to the message', function () {
    $message = TelegramMessage::create()->keyboard('Laravel', request_contact: true);
    expect($message->getPayloadValue('reply_markup'))->toEqual(
        '{"keyboard":[[{"text":"Laravel","request_contact":true,"request_location":false}]],"one_time_keyboard":true,"resize_keyboard":true}'
    );
});

test('an request location keyboard button can be added to the message', function () {
    $message = TelegramMessage::create()->keyboard('Laravel', request_location: true);
    expect($message->getPayloadValue('reply_markup'))->toEqual(
        '{"keyboard":[[{"text":"Laravel","request_contact":false,"request_location":true}]],"one_time_keyboard":true,"resize_keyboard":true}'
    );
});
