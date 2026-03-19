<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use NotificationChannels\Telegram\TelegramMediaGroup;
use NotificationChannels\Telegram\Tests\TestSupport\TestMediaGroupNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;

it('can add remote photos to a media group', function () {
    $group = TelegramMediaGroup::create()
        ->to(12345)
        ->photo('https://example.com/one.jpg', 'First image')
        ->photo('https://example.com/two.jpg');

    expect($group->hasAttachments())->toBeFalse()
        ->and($group->toArray())->toMatchArray([
            'chat_id' => 12345,
            'media' => '[{"type":"photo","media":"https:\/\/example.com\/one.jpg","caption":"First image","parse_mode":"Markdown"},{"type":"photo","media":"https:\/\/example.com\/two.jpg"}]',
        ]);
});

it('uses the current parse mode for captions', function () {
    $group = TelegramMediaGroup::create()
        ->parseMode('HTML')
        ->photo('https://example.com/one.jpg', '<b>First</b>');

    expect($group->toArray()['media'])->toBe(
        '[{"type":"photo","media":"https:\/\/example.com\/one.jpg","caption":"<b>First<\/b>","parse_mode":"HTML"}]'
    );
});

it('creates multipart payloads for uploaded media', function () {
    $group = TelegramMediaGroup::create()
        ->to(12345)
        ->document('inline content', 'Document caption', 'report.txt');

    $payload = $group->toArray();

    expect($group->hasAttachments())->toBeTrue()
        ->and($payload)->toHaveCount(3)
        ->and($payload[0])->toMatchArray([
            'name' => 'chat_id',
            'contents' => 12345,
        ])
        ->and($payload[1])->toMatchArray([
            'name' => 'media',
            'contents' => '[{"type":"document","media":"attach:\/\/file0","caption":"Document caption","parse_mode":"Markdown"}]',
        ])
        ->and($payload[2])->toMatchArray([
            'name' => 'file0',
            'filename' => 'report.txt',
        ]);
});

it('can apply common telegram send options to media groups', function () {
    $group = TelegramMediaGroup::create()
        ->to(12345)
        ->messageThreadId(77)
        ->directMessagesTopicId(12)
        ->protectContent()
        ->allowPaidBroadcast()
        ->messageEffectId('effect-1')
        ->replyParameters(['message_id' => 9])
        ->suggestedPostParameters(['price' => 100])
        ->photo('https://example.com/one.jpg');

    expect($group->toArray())->toMatchArray([
        'chat_id' => 12345,
        'message_thread_id' => 77,
        'direct_messages_topic_id' => 12,
        'protect_content' => true,
        'allow_paid_broadcast' => true,
        'message_effect_id' => 'effect-1',
        'reply_parameters' => '{"message_id":9}',
        'suggested_post_parameters' => '{"price":100}',
    ]);
});

it('can send a media group', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestMediaGroupNotification;
    $group = $notification->toTelegram($notifiable);

    $expectedResponse = [
        [
            'ok' => true,
            'result' => ['message_id' => 1],
        ],
        [
            'ok' => true,
            'result' => ['message_id' => 2],
        ],
    ];

    $this->telegram
        ->shouldReceive('sendMediaGroup')
        ->with($group->toArray(), false)
        ->once()
        ->andReturn(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode($expectedResponse))
        );

    expect($this->channel->send($notifiable, $notification))->toBe($expectedResponse);
});
