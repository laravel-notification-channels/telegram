<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use Illuminate\Support\Facades\View;
use Mockery;
use NotificationChannels\Telegram\Enums\FileType;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\TelegramFile;
use NotificationChannels\Telegram\Tests\TestSupport\TestFileNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use Psr\Http\Message\StreamInterface;

beforeEach(function () {
    $this->telegramFileCaption = 'Test content';
    $this->telegramFile = new TelegramFile($this->telegramFileCaption);
});

it('can be instantiated', function () {
    expect($this->telegramFile)->toBeInstanceOf(TelegramFile::class)
        ->and($this->telegramFile->getPayloadValue('caption'))->toBe($this->telegramFileCaption)
        ->and($this->telegramFile->getPayloadValue('parse_mode'))->toBe(ParseMode::Markdown->value);
});

it('can be created using static method', function () {
    $file = TelegramFile::create('Static content');

    expect($file)->toBeInstanceOf(TelegramFile::class)
        ->and($file->toArray()['caption'])->toBe('Static content');
});

it('can set content', function () {
    $this->telegramFile->content('Updated content');

    expect($this->telegramFile->getPayloadValue('caption'))->toBe('Updated content');
});

it('can handle file URLs', function () {
    $fileUrl = 'https://example.com/file.pdf';
    $this->telegramFile->file($fileUrl, FileType::Document);

    expect($this->telegramFile->getPayloadValue(FileType::Document->value))->toBe($fileUrl)
        ->and($this->telegramFile->type)->toBe(FileType::Document);
});

it('can handle Telegram file IDs', function () {
    $fileId = 'ABC123DEF456GHI789';
    $this->telegramFile->file($fileId, FileType::Photo);

    expect($this->telegramFile->getPayloadValue(FileType::Photo->value))->toBe($fileId)
        ->and($this->telegramFile->type)->toBe(FileType::Photo);
});

it('throws exception on invalid file identifier', function () {
    $this->telegramFile->file('invalid/path/with/slashes', FileType::Document);
})->throws(CouldNotSendNotification::class);

it('rejects a directory path as a file', function () {
    $this->telegramFile->file(__DIR__, FileType::Document);
})->throws(CouldNotSendNotification::class, 'Invalid file identifier: '.__DIR__);

it('strips unsupported captions from the multipart payload', function () {
    $mockStream = Mockery::mock(StreamInterface::class);

    $this->telegramFile
        ->content('This caption must not survive')
        ->file($mockStream, FileType::VideoNote);

    $names = array_column($this->telegramFile->toArray(), 'name');

    expect($names)->toContain('video_note')
        ->not->toContain('caption');
});

it('ignores malformed file payload entries in toMultipart', function () {
    $this->telegramFile->options(['file' => ['name' => 'photo']]);

    $names = array_column($this->telegramFile->toMultipart(), 'name');

    expect($names)->not->toContain('photo');
});

it('can add a photo', function () {
    $url = 'https://example.com/image.jpg';
    $this->telegramFile->photo($url);

    expect($this->telegramFile->type)->toBe(FileType::Photo)
        ->and($this->telegramFile->getPayloadValue(FileType::Photo->value))->toBe($url);
});

it('can add an audio file', function () {
    $url = 'https://example.com/audio.mp3';
    $this->telegramFile->audio($url);

    expect($this->telegramFile->type)->toBe(FileType::Audio)
        ->and($this->telegramFile->getPayloadValue(FileType::Audio->value))->toBe($url);
});

it('can add a document with custom filename', function () {
    $url = 'https://example.com/doc.pdf';
    $filename = 'custom.pdf';
    $this->telegramFile->document($url, $filename);

    $multipart = $this->telegramFile->toMultipart();

    expect($multipart)->toBeArray()
        ->and($multipart)->toHaveKeys([0, 1, 2])
        ->and($multipart[0])->toHaveKey('name', 'caption')
        ->and($multipart[0])->toHaveKey('contents', $this->telegramFile->getPayloadValue('caption'))

        ->and($multipart[1])->toHaveKey('name', 'parse_mode')
        ->and($multipart[1])->toHaveKey('contents', $this->telegramFile->getPayloadValue('parse_mode'))

        ->and($multipart[2])->toHaveKey('name', $this->telegramFile->type->value)
        ->and($multipart[2])->toHaveKey('contents', $url)
        ->and($multipart[2])->toHaveKey('filename', $filename);
});

it('can send document with content on-fly', function () {
    $content = 'Hello Text Content';
    $filename = 'hello.txt';
    $this->telegramFile->document($content, $filename);

    $multipart = $this->telegramFile->toMultipart();

    expect($multipart)->toBeArray()
        ->and($multipart)->toHaveKeys([0, 1, 2])
        ->and($multipart[0])->toHaveKey('name', 'caption')
        ->and($multipart[0])->toHaveKey('contents', $this->telegramFile->getPayloadValue('caption'))

        ->and($multipart[1])->toHaveKey('name', 'parse_mode')
        ->and($multipart[1])->toHaveKey('contents', $this->telegramFile->getPayloadValue('parse_mode'))

        ->and($multipart[2])->toHaveKey('name', $this->telegramFile->type->value)
        ->and($multipart[2])->toHaveKey('contents', $content)
        ->and($multipart[2])->toHaveKey('filename', $filename);
});

it('can add a video', function () {
    $url = 'https://example.com/video.mp4';
    $this->telegramFile->video($url);

    expect($this->telegramFile->type)->toBe(FileType::Video)
        ->and($this->telegramFile->getPayloadValue(FileType::Video->value))->toBe($url);
});

it('can add an animation', function () {
    $url = 'https://example.com/animation.gif';
    $this->telegramFile->animation($url);

    expect($this->telegramFile->type)->toBe(FileType::Animation)
        ->and($this->telegramFile->getPayloadValue(FileType::Animation->value))->toBe($url);
});

it('can add a voice message', function () {
    $url = 'https://example.com/voice.ogg';
    $this->telegramFile->voice($url);

    expect($this->telegramFile->type)->toBe(FileType::Voice)
        ->and($this->telegramFile->getPayloadValue(FileType::Voice->value))->toBe($url);
});

it('can add a video note', function () {
    $url = 'https://example.com/videonote.mp4';
    $this->telegramFile->videoNote($url);

    expect($this->telegramFile->type)->toBe(FileType::VideoNote)
        ->and($this->telegramFile->getPayloadValue(FileType::VideoNote->value))->toBe($url);
});

it('can add a sticker', function () {
    $url = 'https://example.com/sticker.webp';
    $this->telegramFile->sticker($url);

    expect($this->telegramFile->type)->toBe(FileType::Sticker)
        ->and($this->telegramFile->getPayloadValue(FileType::Sticker->value))->toBe($url);
});

it('can add a live photo', function () {
    $url = 'https://example.com/live.jpg';
    $this->telegramFile->livePhoto($url);

    expect($this->telegramFile->type)->toBe(FileType::LivePhoto)
        ->and($this->telegramFile->getPayloadValue(FileType::LivePhoto->value))->toBe($url);
});

it('exposes file type values on the enum', function () {
    expect(FileType::LivePhoto->value)->toBe('live_photo')
        ->and(FileType::toArray())->toHaveKey('LivePhoto', 'live_photo');
});

it('can use a view as content', function () {
    View::shouldReceive('make')
        ->once()
        ->with('telegram', ['key' => 'value'], [])
        ->andReturn(Mockery::mock(['render' => 'View content']));

    $this->telegramFile->view('telegram', ['key' => 'value']);

    expect($this->telegramFile->getPayloadValue('caption'))->toBe('View content');
});

it('can check if a file is attached', function () {
    expect($this->telegramFile->hasFile())->toBeFalse();

    $mockStream = Mockery::mock(StreamInterface::class);
    $mockStream->shouldReceive('detach')->andReturn(null);

    $this->telegramFile->file($mockStream, FileType::Document);

    expect($this->telegramFile->hasFile())->toBeTrue();
});

it('removes caption for unsupported file types', function () {
    // VideoNote doesn't support captions
    $this->telegramFile->content('This will be removed')->videoNote('https://example.com/video_note.mp4');

    expect($this->telegramFile->toArray())->not->toHaveKey('caption');

    // Sticker doesn't support captions
    $file = TelegramFile::create('This will be removed')->sticker('https://example.com/sticker.webp');

    expect($file->toArray())->not->toHaveKey('caption');
});

it('maintains caption for supported file types', function () {
    $caption = 'This should remain';

    // Photo supports captions
    $this->telegramFile->content($caption)->photo('https://example.com/photo.jpg');

    expect($this->telegramFile->getPayloadValue('caption'))->toBe($caption);
});

it('creates multipart array for file uploads', function () {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, 'test data');
    rewind($resource);

    $this->telegramFile->file($resource, FileType::Document, 'test.txt');

    $multipart = $this->telegramFile->toMultipart();

    expect($multipart)->toBeArray()
        ->and($multipart)->toHaveKeys([0, 1, 2])
        ->and($multipart[0])->toHaveKey('name', 'caption')
        ->and($multipart[0])->toHaveKey('contents', $this->telegramFile->getPayloadValue('caption'))

        ->and($multipart[1])->toHaveKey('name', 'parse_mode')
        ->and($multipart[1])->toHaveKey('contents', $this->telegramFile->getPayloadValue('parse_mode'))

        ->and($multipart[2])->toHaveKey('name', $this->telegramFile->type->value)
        ->and($multipart[2])->toHaveKey('contents')
        ->and($multipart[2])->toHaveKey('filename', 'test.txt');

    fclose($resource);
});

it('handles resources for file uploads', function () {
    $resource = fopen('php://memory', 'r+');
    fwrite($resource, 'mock file content');
    rewind($resource);

    $this->telegramFile->file($resource, FileType::Document);

    $multipart = $this->telegramFile->toMultipart();

    expect($multipart)->toBeArray()
        ->and($multipart)->toHaveKeys([0, 1, 2])
        ->and($multipart[2])->toHaveKey('name', $this->telegramFile->type->value)
        ->and($multipart[2])->toHaveKey('contents');

    fclose($resource);
});

it('supports file type from string', function () {
    $this->telegramFile->file('https://example.com/file.mp3', 'audio');

    expect($this->telegramFile->type)->toBe(FileType::Audio)
        ->and($this->telegramFile->getPayloadValue(FileType::Audio->value))->toBe('https://example.com/file.mp3');
});

it('throws for an invalid file type string', function () {
    $this->telegramFile->file('https://example.com/file.xyz', 'invalid_type');
})->throws(CouldNotSendNotification::class, 'Invalid file type: invalid_type');

it('can attach a telegram file id explicitly', function () {
    $this->telegramFile->fileId('ABC-123_xyz', FileType::Photo);

    expect($this->telegramFile->type)->toBe(FileType::Photo)
        ->and($this->telegramFile->getPayloadValue(FileType::Photo->value))->toBe('ABC-123_xyz')
        ->and($this->telegramFile->hasFile())->toBeFalse();
});

it('rejects an invalid telegram file id', function () {
    $this->telegramFile->fileId('not a file id!');
})->throws(CouldNotSendNotification::class, 'Invalid file identifier: not a file id!');

it('can attach a remote url explicitly', function () {
    $this->telegramFile->url('https://example.com/file.pdf', FileType::Document);

    expect($this->telegramFile->type)->toBe(FileType::Document)
        ->and($this->telegramFile->getPayloadValue(FileType::Document->value))->toBe('https://example.com/file.pdf')
        ->and($this->telegramFile->hasFile())->toBeFalse();
});

it('rejects an invalid remote url', function () {
    $this->telegramFile->url('not a url');
})->throws(CouldNotSendNotification::class, 'Invalid file identifier: not a url');

it('can set caption with content method', function () {
    $caption = 'New caption with *markdown*';
    $this->telegramFile->content($caption);

    expect($this->telegramFile->getPayloadValue('caption'))->toBe($caption);
});

it('can set caption entities and show caption above media', function () {
    $this->telegramFile
        ->captionEntities([
            [
                'offset' => 0,
                'length' => 4,
                'type' => 'bold',
            ],
        ])
        ->showCaptionAboveMedia();

    expect($this->telegramFile->toArray())->toMatchArray([
        'caption_entities' => '[{"offset":0,"length":4,"type":"bold"}]',
        'show_caption_above_media' => true,
    ]);
});

it('returns a multipart payload from toArray when a file is attached', function () {
    $this->telegramFile->document('Hello Text Content', 'hello.txt');

    $payload = $this->telegramFile->toArray();

    expect($payload)->toBeList()
        ->and($payload[0])->toHaveKey('name', 'caption')
        ->and($payload[2])->toHaveKey('name', 'document')
        ->and($payload[2])->toHaveKey('filename', 'hello.txt');
});

it('sends file through Telegram', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestFileNotification;

    $expectedResponse = $this->makeMockResponse([
        'document' => collect($notification->toTelegram($notifiable)->toArray())
            ->except('chat_id')
            ->toArray(),
    ]);

    $actualResponse = $this->sendFileMockNotification(
        $notifiable,
        $notification,
        $expectedResponse
    );

    expect($actualResponse)->toBe($expectedResponse);
});
