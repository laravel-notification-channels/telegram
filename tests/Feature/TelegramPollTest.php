<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\TelegramPoll;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestPollNotification;

it('accepts question when constructed', function () {
    $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
    expect($message->getPayloadValue('question'))->toEqual("Aren't Laravel Notification Channels awesome?");
});

test('the recipients chat id can be set', function () {
    $message = new TelegramPoll;
    $message->to(12345);
    expect($message->getPayloadValue('chat_id'))->toEqual(12345);
});

test('the question message can be set', function () {
    $message = new TelegramPoll;
    $message->question("Aren't Laravel Notification Channels awesome?");
    expect($message->getPayloadValue('question'))->toEqual("Aren't Laravel Notification Channels awesome?");
});

it('accepts question when created', function () {
    $message = TelegramPoll::create("Aren't Laravel Notification Channels awesome?");
    expect($message->getPayloadValue('question'))->toEqual("Aren't Laravel Notification Channels awesome?");
});

test('the options can be set for the question', function () {
    $message = new TelegramPoll;
    $message->choices(['Yes', 'No']);
    expect($message->getPayloadValue('options'))->toEqual('[{"text":"Yes"},{"text":"No"}]');
});

test('the options are re-indexed as a list for string keyed choices', function () {
    $message = new TelegramPoll;
    $message->choices(['y' => 'Yes', 'n' => 'No']);
    expect($message->getPayloadValue('options'))->toEqual('[{"text":"Yes"},{"text":"No"}]');
});

test('the options accept input poll option arrays', function () {
    $message = new TelegramPoll;
    $message->choices([
        'A',
        ['text' => 'B', 'media' => ['type' => 'photo', 'media' => 'https://example.com/b.jpg']],
    ]);

    expect($message->getPayloadValue('options'))
        ->toEqual('[{"text":"A"},{"text":"B","media":{"type":"photo","media":"https:\/\/example.com\/b.jpg"}}]');
});

it('can determine if the recipient chat id has not been set', function () {
    $message = new TelegramPoll;
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse();
});

it('can return the payload as an array', function () {
    $message = new TelegramPoll("Aren't Laravel Notification Channels awesome?");
    $message->to(12345);
    $message->choices(['Yes', 'No']);
    $expected = [
        'chat_id' => 12345,
        'question' => "Aren't Laravel Notification Channels awesome?",
        'options' => '[{"text":"Yes"},{"text":"No"}]',
    ];

    expect($message->toArray())->toEqual($expected);
});

test('the poll description can be set with a parse mode', function () {
    $message = new TelegramPoll;
    $message->description('Pick wisely')
        ->descriptionParseMode(ParseMode::HTML);

    expect($message->getPayloadValue('description'))->toEqual('Pick wisely')
        ->and($message->getPayloadValue('description_parse_mode'))->toEqual('HTML');
});

test('the poll description parse mode accepts a string', function () {
    $message = new TelegramPoll;
    $message->descriptionParseMode('MarkdownV2');

    expect($message->getPayloadValue('description_parse_mode'))->toEqual('MarkdownV2');
});

test('the poll description entities can be set', function () {
    $message = new TelegramPoll;
    $message->descriptionEntities([
        ['offset' => 0, 'length' => 4, 'type' => 'bold'],
    ]);

    expect($message->getPayloadValue('description_entities'))
        ->toEqual('[{"offset":0,"length":4,"type":"bold"}]');
});

test('the poll explanation can be set with a parse mode', function () {
    $message = new TelegramPoll;
    $message->explanation('Because it is.')
        ->explanationParseMode(ParseMode::HTML);

    expect($message->getPayloadValue('explanation'))->toEqual('Because it is.')
        ->and($message->getPayloadValue('explanation_parse_mode'))->toEqual('HTML');
});

test('the poll explanation parse mode accepts a string', function () {
    $message = new TelegramPoll;
    $message->explanationParseMode('MarkdownV2');

    expect($message->getPayloadValue('explanation_parse_mode'))->toEqual('MarkdownV2');
});

test('the poll explanation entities can be set', function () {
    $message = new TelegramPoll;
    $message->explanationEntities([
        ['offset' => 0, 'length' => 7, 'type' => 'italic'],
    ]);

    expect($message->getPayloadValue('explanation_entities'))
        ->toEqual('[{"offset":0,"length":7,"type":"italic"}]');
});

test('the poll type can be set', function () {
    $message = new TelegramPoll;
    $message->type('regular');

    expect($message->getPayloadValue('type'))->toEqual('regular');
});

test('a quiz can be created with a single correct option', function () {
    $message = new TelegramPoll;
    $message->choices(['Yes', 'No'])->quiz(1);

    expect($message->getPayloadValue('type'))->toEqual('quiz')
        ->and($message->getPayloadValue('correct_option_ids'))->toEqual('[1]');
});

test('a quiz can be created with multiple correct options', function () {
    $message = new TelegramPoll;
    $message->choices(['A', 'B', 'C'])->quiz([0, 2]);

    expect($message->getPayloadValue('type'))->toEqual('quiz')
        ->and($message->getPayloadValue('correct_option_ids'))->toEqual('[0,2]');
});

test('the quiz correct option ids are re-indexed as a list', function () {
    $message = new TelegramPoll;
    $message->quiz([2 => 1, 5 => 3]);

    expect($message->getPayloadValue('correct_option_ids'))->toEqual('[1,3]');
});

test('the poll voting behaviour flags can be set', function () {
    $message = new TelegramPoll;
    $message->allowsRevoting()
        ->shuffleOptions()
        ->allowAddingOptions()
        ->hideResultsUntilCloses()
        ->membersOnly()
        ->isAnonymous()
        ->allowsMultipleAnswers();

    expect($message->toArray())->toMatchArray([
        'allows_revoting' => true,
        'shuffle_options' => true,
        'allow_adding_options' => true,
        'hide_results_until_closes' => true,
        'members_only' => true,
        'is_anonymous' => true,
        'allows_multiple_answers' => true,
    ]);
});

test('the poll voting behaviour flags can be disabled', function () {
    $message = new TelegramPoll;
    $message->allowsRevoting(false)
        ->shuffleOptions(false)
        ->allowAddingOptions(false)
        ->hideResultsUntilCloses(false)
        ->membersOnly(false)
        ->isAnonymous(false)
        ->allowsMultipleAnswers(false);

    expect($message->toArray())->toMatchArray([
        'allows_revoting' => false,
        'shuffle_options' => false,
        'allow_adding_options' => false,
        'hide_results_until_closes' => false,
        'members_only' => false,
        'is_anonymous' => false,
        'allows_multiple_answers' => false,
    ]);
});

test('the poll country codes can be set', function () {
    $message = new TelegramPoll;
    $message->countryCodes(['us' => 'US', 'gb' => 'GB']);

    expect($message->getPayloadValue('country_codes'))->toEqual('["US","GB"]');
});

test('the poll open period and close date can be set', function () {
    $message = new TelegramPoll;
    $message->openPeriod(600)->closeDate(1750000000);

    expect($message->getPayloadValue('open_period'))->toEqual(600)
        ->and($message->getPayloadValue('close_date'))->toEqual(1750000000);
});

test('the poll media and explanation media can be set', function () {
    $message = new TelegramPoll;
    $message->media(['type' => 'photo', 'media' => 'https://example.com/question.jpg'])
        ->explanationMedia(['type' => 'photo', 'media' => 'https://example.com/answer.jpg']);

    expect($message->getPayloadValue('media'))
        ->toEqual('{"type":"photo","media":"https:\/\/example.com\/question.jpg"}')
        ->and($message->getPayloadValue('explanation_media'))
        ->toEqual('{"type":"photo","media":"https:\/\/example.com\/answer.jpg"}');
});

it('can send a poll', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestPollNotification;

    $expectedResponse = $this->makeMockResponse([
        'poll' => [
            'id' => '1234567890101112',
            'question' => "Isn't Telegram Notification Channel Awesome?",
            'options' => [
                [
                    'text' => 'Yes',
                    'voter_count' => 0,
                ],
                [
                    'text' => 'No',
                    'voter_count' => 0,
                ],
            ],
            'total_voter_count' => 0,
            'is_closed' => false,
            'is_anonymous' => true,
            'type' => 'regular',
            'allows_multiple_answers' => false,
        ],
    ]);

    $actualResponse = $this->sendMockNotification('sendPoll', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
