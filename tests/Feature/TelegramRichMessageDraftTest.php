<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Psr7\Response;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\TelegramRichMessage;
use NotificationChannels\Telegram\TelegramRichMessageDraft;

/**
 * Decode the `rich_message` param of a draft back into an array.
 */
function draftRichMessage(TelegramRichMessageDraft $draft): array
{
    return json_decode($draft->toArray()['rich_message'], true, 512, JSON_THROW_ON_ERROR);
}

it('adds the draft id to the payload', function () {
    $draft = TelegramRichMessageDraft::create('*Thinking...*')
        ->to(12345)
        ->draftId(777);

    expect($draft->getPayloadValue('draft_id'))->toBe(777)
        ->and($draft->toArray())->toBe([
            'chat_id' => 12345,
            'draft_id' => 777,
            'rich_message' => '{"markdown":"*Thinking...*"}',
        ]);
});

it('rejects a zero draft id', function () {
    TelegramRichMessageDraft::create()->draftId(0);
})->throws(CouldNotSendNotification::class, 'Invalid rich message draft identifier: 0');

it('accepts negative draft ids', function () {
    expect(TelegramRichMessageDraft::create()->draftId(-1)->getPayloadValue('draft_id'))->toBe(-1);
});

it('cannot be sent without a draft id', function () {
    TelegramRichMessageDraft::create('Hi')->to(12345)->send();
})->throws(CouldNotSendNotification::class, 'You must provide a draft identifier with `draftId()` to send a rich message draft.');

it('sends the draft through the send rich message draft endpoint', function () {
    $expectedResponse = new Response(200, [], json_encode(['ok' => true]));

    $this->telegram
        ->shouldReceive('sendRichMessageDraft')
        ->once()
        ->with([
            'chat_id' => 12345,
            'draft_id' => 777,
            'rich_message' => '{"markdown":"Hello"}',
        ])
        ->andReturns($expectedResponse);

    $draft = TelegramRichMessageDraft::create()
        ->to(12345)
        ->draftId(777)
        ->markdown('Hello');

    expect($draft->send())->toBe($expectedResponse);
});

it('replaces the content of the draft on every send', function () {
    $this->telegram
        ->shouldReceive('sendRichMessageDraft')
        ->once()
        ->with([
            'chat_id' => 12345,
            'draft_id' => 777,
            'rich_message' => '{"markdown":"Once"}',
        ])
        ->andReturns(new Response(200, [], json_encode(['ok' => true])));

    $this->telegram
        ->shouldReceive('sendRichMessageDraft')
        ->once()
        ->with([
            'chat_id' => 12345,
            'draft_id' => 777,
            'rich_message' => '{"markdown":"Once upon a time"}',
        ])
        ->andReturns(new Response(200, [], json_encode(['ok' => true])));

    $draft = TelegramRichMessageDraft::create()
        ->to(12345)
        ->draftId(777);

    $buffer = '';

    foreach (['Once', ' upon a time'] as $chunk) {
        $buffer .= $chunk;

        $draft->markdown($buffer)->send();
    }

    expect(draftRichMessage($draft))->toBe(['markdown' => 'Once upon a time']);
});

it('finalizes the draft through the send rich message endpoint without the draft id', function () {
    $expectedResponse = new Response(200, [], json_encode(['ok' => true]));

    $this->telegram
        ->shouldReceive('sendRichMessage')
        ->once()
        ->with([
            'chat_id' => 12345,
            'rich_message' => '{"markdown":"All done"}',
        ])
        ->andReturns($expectedResponse);

    $draft = TelegramRichMessageDraft::create()
        ->to(12345)
        ->draftId(777)
        ->markdown('All done');

    expect($draft->finalize())->toBe($expectedResponse)
        ->and($draft->getPayloadValue('draft_id'))->toBe(777)
        ->and($draft->toArray())->toHaveKey('draft_id');
});

it('adds the stop generation params to the payload', function () {
    $draft = TelegramRichMessageDraft::create('*Thinking...*')
        ->to(12345)
        ->draftId(777)
        ->canStop()
        ->keepOnStop();

    expect($draft->toArray())->toBe([
        'chat_id' => 12345,
        'draft_id' => 777,
        'can_stop' => true,
        'keep_on_stop' => true,
        'rich_message' => '{"markdown":"*Thinking...*"}',
    ]);
});

it('drops the stop generation params when the draft is finalized', function () {
    $expectedResponse = new Response(200, [], json_encode(['ok' => true]));

    $this->telegram
        ->shouldReceive('sendRichMessage')
        ->once()
        ->with([
            'chat_id' => 12345,
            'rich_message' => '{"markdown":"All done"}',
        ])
        ->andReturns($expectedResponse);

    $draft = TelegramRichMessageDraft::create('All done')
        ->to(12345)
        ->draftId(777)
        ->canStop()
        ->keepOnStop(false);

    expect($draft->finalize())->toBe($expectedResponse)
        ->and($draft->toArray())
        ->toHaveKeys(['draft_id', 'can_stop', 'keep_on_stop']);
});

it('can be finalized without a draft id', function () {
    $expectedResponse = new Response(200, [], json_encode(['ok' => true]));

    $this->telegram
        ->shouldReceive('sendRichMessage')
        ->once()
        ->with([
            'chat_id' => 12345,
            'rich_message' => '{"markdown":"All done"}',
        ])
        ->andReturns($expectedResponse);

    $draft = TelegramRichMessageDraft::create('All done')->to(12345);

    expect($draft->finalize())->toBe($expectedResponse);
});

it('keeps returning the draft instance while chaining', function () {
    $draft = TelegramRichMessageDraft::create()
        ->draftId(777)
        ->markdown('*Hi*')
        ->paragraph('Hello there!');

    expect($draft)->toBeInstanceOf(TelegramRichMessageDraft::class)
        ->and(TelegramRichMessageDraft::create())->toBeInstanceOf(TelegramRichMessageDraft::class)
        ->and($draft->to(12345)->rtl()->divider()->block(['type' => 'anchor', 'name' => 'end']))
        ->toBeInstanceOf(TelegramRichMessageDraft::class);
});

it('builds the same rich message as the parent class', function () {
    $build = fn (TelegramRichMessage $message): TelegramRichMessage => $message
        ->markdown('*Hello*')
        ->heading('Title')
        ->paragraph('Body')
        ->listBlock(['First', 'Second'])
        ->divider()
        ->media('cover', ['type' => 'photo', 'media' => 'https://example.com/cover.jpg'])
        ->rtl()
        ->skipEntityDetection()
        ->footer('Bye');

    $message = $build(TelegramRichMessage::create());
    $draft = $build(TelegramRichMessageDraft::create());

    expect($draft->getRichMessage())->toBe($message->getRichMessage())
        ->and($draft->toArray()['rich_message'])->toBe($message->toArray()['rich_message']);
});
