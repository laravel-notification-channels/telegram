<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use Illuminate\Support\Facades\View;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\TelegramRichMessage;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestRichMessageNotification;

/**
 * Decode the `rich_message` param back into an array.
 */
function richMessage(TelegramRichMessage $message): array
{
    return json_decode($message->toArray()['rich_message'], true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Decode the `rich_message` param and return its blocks.
 */
function richBlocks(TelegramRichMessage $message): array
{
    return richMessage($message)['blocks'];
}

it('accepts markdown content when constructed', function () {
    $message = new TelegramRichMessage('*Hello* there!');

    expect(richMessage($message))->toBe(['markdown' => '*Hello* there!']);
});

it('accepts markdown content when created', function () {
    $message = TelegramRichMessage::create('*Hello* there!');

    expect(richMessage($message))->toBe(['markdown' => '*Hello* there!']);
});

it('does not set any content when constructed without markdown', function () {
    expect(richMessage(new TelegramRichMessage))->toBe([])
        ->and(richMessage(TelegramRichMessage::create()))->toBe([]);
});

test('the markdown and html content can be set', function () {
    $message = TelegramRichMessage::create()
        ->markdown('*bold*')
        ->html('<b>bold</b>');

    expect(richMessage($message))->toBe([
        'markdown' => '*bold*',
        'html' => '<b>bold</b>',
    ]);
});

test('the content flags can be set', function () {
    $message = TelegramRichMessage::create()
        ->rtl()
        ->skipEntityDetection();

    expect(richMessage($message))->toBe([
        'is_rtl' => true,
        'skip_entity_detection' => true,
    ]);
});

test('the content flags can be disabled', function () {
    $message = TelegramRichMessage::create()
        ->rtl(false)
        ->skipEntityDetection(false);

    expect(richMessage($message))->toBe([
        'is_rtl' => false,
        'skip_entity_detection' => false,
    ]);
});

test('a blade view can be rendered as the html content', function () {
    View::addLocation(__DIR__.'/../TestSupport');

    $message = TelegramRichMessage::create()
        ->view('TestViewFile', ['name' => 'Telegram Notification Channel']);

    expect(richMessage($message))->toBe([
        'html' => "<h1>Hello, Telegram Notification Channel</h1>\n",
    ]);
});

test('media can be attached and referenced by id', function () {
    $message = TelegramRichMessage::create('See tg://photo?id=cover')
        ->media('cover', ['type' => 'photo', 'media' => 'https://example.com/cover.jpg'])
        ->media('clip-1_A', ['type' => 'video', 'media' => 'https://example.com/clip.mp4']);

    expect(richMessage($message))->toBe([
        'markdown' => 'See tg://photo?id=cover',
        'media' => [
            [
                'id' => 'cover',
                'media' => ['type' => 'photo', 'media' => 'https://example.com/cover.jpg'],
            ],
            [
                'id' => 'clip-1_A',
                'media' => ['type' => 'video', 'media' => 'https://example.com/clip.mp4'],
            ],
        ],
    ]);
});

it('rejects invalid media identifiers', function (string $id) {
    TelegramRichMessage::create()->media($id, ['type' => 'photo', 'media' => 'https://example.com/a.jpg']);
})->with([
    'empty' => [''],
    'invalid character' => ['cover photo'],
    'dot' => ['cover.jpg'],
    'too long' => [str_repeat('a', 65)],
])->throws(CouldNotSendNotification::class, 'Invalid rich message media identifier');

test('a paragraph block can be added', function () {
    $message = TelegramRichMessage::create()->paragraph('Hello there!');

    expect(richBlocks($message))->toBe([
        ['type' => 'paragraph', 'text' => 'Hello there!'],
    ]);
});

test('a paragraph block accepts rich text arrays', function () {
    $message = TelegramRichMessage::create()->paragraph([
        'Hello ',
        ['type' => 'bold', 'text' => 'there'],
    ]);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'paragraph',
            'text' => ['Hello ', ['type' => 'bold', 'text' => 'there']],
        ],
    ]);
});

test('a heading block can be added', function () {
    $message = TelegramRichMessage::create()
        ->heading('Title')
        ->heading('Subtitle', 2);

    expect(richBlocks($message))->toBe([
        ['type' => 'heading', 'text' => 'Title', 'size' => 1],
        ['type' => 'heading', 'text' => 'Subtitle', 'size' => 2],
    ]);
});

test('a preformatted block can be added with and without a language', function () {
    $message = TelegramRichMessage::create()
        ->preformatted('echo 1;')
        ->preformatted('echo 1;', 'php');

    expect(richBlocks($message))->toBe([
        ['type' => 'pre', 'text' => 'echo 1;'],
        ['type' => 'pre', 'text' => 'echo 1;', 'language' => 'php'],
    ]);
});

test('a footer block can be added', function () {
    $message = TelegramRichMessage::create()->footer('Sent by MyBot');

    expect(richBlocks($message))->toBe([
        ['type' => 'footer', 'text' => 'Sent by MyBot'],
    ]);
});

test('a divider block can be added', function () {
    $message = TelegramRichMessage::create()->divider();

    expect(richBlocks($message))->toBe([
        ['type' => 'divider'],
    ]);
});

test('a mathematical expression block can be added', function () {
    $message = TelegramRichMessage::create()->math('e^{i\pi} + 1 = 0');

    expect(richBlocks($message))->toBe([
        ['type' => 'mathematical_expression', 'expression' => 'e^{i\pi} + 1 = 0'],
    ]);
});

test('an anchor block can be added', function () {
    $message = TelegramRichMessage::create()->anchor('summary');

    expect(richBlocks($message))->toBe([
        ['type' => 'anchor', 'name' => 'summary'],
    ]);
});

test('a blockquote block wraps a string into a paragraph block', function () {
    $message = TelegramRichMessage::create()->blockquote('To be, or not to be.');

    expect(richBlocks($message))->toBe([
        [
            'type' => 'blockquote',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'To be, or not to be.'],
            ],
        ],
    ]);
});

test('a blockquote block accepts blocks and a credit', function () {
    $message = TelegramRichMessage::create()->blockquote(
        [['type' => 'paragraph', 'text' => 'To be, or not to be.']],
        'Shakespeare'
    );

    expect(richBlocks($message))->toBe([
        [
            'type' => 'blockquote',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'To be, or not to be.'],
            ],
            'credit' => 'Shakespeare',
        ],
    ]);
});

test('an expandable blockquote block can be added with and without a credit', function () {
    $message = TelegramRichMessage::create()
        ->expandableBlockquote('To be, or not to be.')
        ->expandableBlockquote(['Long ', ['type' => 'bold', 'text' => 'story']], 'Shakespeare');

    expect(richBlocks($message))->toBe([
        ['type' => 'expandable_blockquote', 'text' => 'To be, or not to be.'],
        [
            'type' => 'expandable_blockquote',
            'text' => ['Long ', ['type' => 'bold', 'text' => 'story']],
            'credit' => 'Shakespeare',
        ],
    ]);
});

test('a buttons block can be added with and without an alignment', function () {
    $buttons = [
        ['text' => 'Docs', 'url' => 'https://example.com/docs'],
        ['text' => 'Confirm', 'callback_data' => 'confirm', 'style' => 'success'],
    ];

    $message = TelegramRichMessage::create()
        ->buttons($buttons)
        ->buttons([1 => $buttons[1]], 'center');

    expect(richBlocks($message))->toBe([
        ['type' => 'buttons', 'buttons' => $buttons],
        ['type' => 'buttons', 'buttons' => [$buttons[1]], 'align' => 'center'],
    ]);
});

test('a pullquote block can be added with and without a credit', function () {
    $message = TelegramRichMessage::create()
        ->pullquote('Stay hungry.')
        ->pullquote('Stay foolish.', ['Steve ', ['type' => 'bold', 'text' => 'Jobs']]);

    expect(richBlocks($message))->toBe([
        ['type' => 'pullquote', 'text' => 'Stay hungry.'],
        [
            'type' => 'pullquote',
            'text' => 'Stay foolish.',
            'credit' => ['Steve ', ['type' => 'bold', 'text' => 'Jobs']],
        ],
    ]);
});

test('a details block can be added', function () {
    $message = TelegramRichMessage::create()
        ->details('More info', [['type' => 'paragraph', 'text' => 'Hidden.']])
        ->details('Open by default', [['type' => 'divider']], true);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'details',
            'summary' => 'More info',
            'blocks' => [['type' => 'paragraph', 'text' => 'Hidden.']],
            'is_open' => false,
        ],
        [
            'type' => 'details',
            'summary' => 'Open by default',
            'blocks' => [['type' => 'divider']],
            'is_open' => true,
        ],
    ]);
});

test('a table block can be added', function () {
    $cells = [
        [['blocks' => [['type' => 'paragraph', 'text' => 'Plan']]]],
        [['blocks' => [['type' => 'paragraph', 'text' => 'Pro']]]],
    ];

    $message = TelegramRichMessage::create()
        ->table($cells)
        ->table($cells, true, true, 'Pricing', true);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'table',
            'cells' => $cells,
            'is_bordered' => false,
            'is_striped' => false,
            'is_compact' => false,
        ],
        [
            'type' => 'table',
            'cells' => $cells,
            'is_bordered' => true,
            'is_striped' => true,
            'is_compact' => true,
            'caption' => 'Pricing',
        ],
    ]);
});

test('a list block wraps string items into paragraph blocks', function () {
    $message = TelegramRichMessage::create()->listBlock(['First', 'Second']);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'list',
            'items' => [
                ['blocks' => [['type' => 'paragraph', 'text' => 'First']]],
                ['blocks' => [['type' => 'paragraph', 'text' => 'Second']]],
            ],
        ],
    ]);
});

test('a list block passes item arrays through and re-indexes them', function () {
    $message = TelegramRichMessage::create()->listBlock([
        3 => [
            'blocks' => [['type' => 'paragraph', 'text' => 'Done']],
            'has_checkbox' => true,
            'is_checked' => true,
            'value' => 1,
            'type' => 'a',
        ],
        7 => 'Todo',
    ]);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'list',
            'items' => [
                [
                    'blocks' => [['type' => 'paragraph', 'text' => 'Done']],
                    'has_checkbox' => true,
                    'is_checked' => true,
                    'value' => 1,
                    'type' => 'a',
                ],
                ['blocks' => [['type' => 'paragraph', 'text' => 'Todo']]],
            ],
        ],
    ]);
});

test('a thinking block can be added', function () {
    $message = TelegramRichMessage::create()->thinking('Let me check that...');

    expect(richBlocks($message))->toBe([
        ['type' => 'thinking', 'text' => 'Let me check that...'],
    ]);
});

test('a map block can be added', function () {
    $message = TelegramRichMessage::create()->map(51.5072, -0.1276, 12, 600, 400);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'map',
            'location' => ['latitude' => 51.5072, 'longitude' => -0.1276],
            'zoom' => 12,
            'width' => 600,
            'height' => 400,
        ],
    ]);
});

test('media blocks can be added', function (string $method, string $type) {
    $media = ['type' => $type, 'media' => 'https://example.com/file'];

    $message = TelegramRichMessage::create()
        ->{$method}($media)
        ->{$method}($media, ['text' => 'A caption', 'credit' => 'Author']);

    expect(richBlocks($message))->toBe([
        ['type' => $type, $type => $media],
        [
            'type' => $type,
            $type => $media,
            'caption' => ['text' => 'A caption', 'credit' => 'Author'],
        ],
    ]);
})->with([
    ['photoBlock', 'photo'],
    ['videoBlock', 'video'],
    ['audioBlock', 'audio'],
    ['animationBlock', 'animation'],
    ['voiceNoteBlock', 'voice_note'],
    ['documentBlock', 'document'],
]);

test('a raw block can be added with the escape hatch', function () {
    $message = TelegramRichMessage::create()->block([
        'type' => 'collage',
        'blocks' => [['type' => 'photo', 'photo' => ['type' => 'photo', 'media' => 'https://example.com/a.jpg']]],
        'caption' => ['text' => 'Gallery'],
    ]);

    expect(richBlocks($message))->toBe([
        [
            'type' => 'collage',
            'blocks' => [['type' => 'photo', 'photo' => ['type' => 'photo', 'media' => 'https://example.com/a.jpg']]],
            'caption' => ['text' => 'Gallery'],
        ],
    ]);
});

test('blocks are appended in the order they are added', function () {
    $message = TelegramRichMessage::create()
        ->heading('Title')
        ->paragraph('Body')
        ->divider()
        ->footer('Bye');

    expect(array_column(richBlocks($message), 'type'))
        ->toBe(['heading', 'paragraph', 'divider', 'footer']);
});

test('the rich message can be read back', function () {
    $message = TelegramRichMessage::create('*Hi*')->divider();

    expect($message->getRichMessage())->toBe([
        'markdown' => '*Hi*',
        'blocks' => [['type' => 'divider']],
    ]);
});

it('can return the payload as an array alongside the shared logic params', function () {
    $message = TelegramRichMessage::create('*Hello*')
        ->to(12345)
        ->disableNotification()
        ->protectContent()
        ->messageThreadId(42)
        ->button('View Invoice', 'https://example.com/invoice');

    expect($message->toArray())->toBe([
        'chat_id' => 12345,
        'disable_notification' => true,
        'protect_content' => true,
        'message_thread_id' => 42,
        'reply_markup' => '{"inline_keyboard":[[{"text":"View Invoice","url":"https:\/\/example.com\/invoice"}]]}',
        'rich_message' => '{"markdown":"*Hello*"}',
    ]);
});

it('always includes the rich message param even when empty', function () {
    expect(TelegramRichMessage::create()->toArray())->toBe(['rich_message' => '{}']);
});

it('can determine if the recipient chat id has not been set', function () {
    $message = TelegramRichMessage::create();
    expect($message->toNotGiven())->toBeTrue();

    $message->to(12345);
    expect($message->toNotGiven())->toBeFalse()
        ->and($message->getPayloadValue('chat_id'))->toEqual(12345);
});

it('can send a rich message', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestRichMessageNotification;

    $expectedResponse = $this->makeMockResponse([
        'rich_message' => [
            'blocks' => [
                ['type' => 'heading', 'text' => 'Invoice Paid', 'size' => 1],
                ['type' => 'paragraph', 'text' => 'Thanks for your payment!'],
                ['type' => 'divider'],
            ],
        ],
    ]);

    $actualResponse = $this->sendMockNotification('sendRichMessage', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});
