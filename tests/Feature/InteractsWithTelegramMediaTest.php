<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use GuzzleHttp\Psr7\Utils;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Traits\InteractsWithTelegramMedia;
use Psr\Http\Message\StreamInterface;

final class TestMediaInteractionHarness
{
    use InteractsWithTelegramMedia;

    /**
     * @return array{name: string, contents: mixed, filename?: string}
     */
    public function makeItem(string $name, mixed $contents, ?string $filename = null): array
    {
        return $this->makeMultipartItem($name, $contents, $filename);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{name: string, contents: mixed, filename?: string}>
     */
    public function toMultipartItems(array $payload): array
    {
        return $this->payloadToMultipart($payload);
    }

    public function readable(string $file): bool
    {
        return $this->isReadableFile($file);
    }

    public function remote(string $file, ?string $filename = null): bool
    {
        return $this->isRemoteIdentifier($file, $filename);
    }

    public function normalize(mixed $file, ?string $filename = null, string $invalidMessage = 'Invalid file input'): StreamInterface
    {
        return $this->normalizeUpload($file, $filename, $invalidMessage);
    }
}

it('creates multipart items and payload multipart arrays', function () {
    $harness = new TestMediaInteractionHarness;

    expect($harness->makeItem('document', 'contents', 'report.txt'))->toBe([
        'name' => 'document',
        'contents' => 'contents',
        'filename' => 'report.txt',
    ])->and($harness->toMultipartItems([
        'chat_id' => 123,
        'caption' => 'Report',
    ]))->toBe([
        ['name' => 'chat_id', 'contents' => 123],
        ['name' => 'caption', 'contents' => 'Report'],
    ]);
});

it('recognizes readable files and remote identifiers', function () {
    $harness = new TestMediaInteractionHarness;
    $file = tempnam(sys_get_temp_dir(), 'telegram-media-');
    file_put_contents($file, 'local file');

    try {
        expect($harness->readable($file))
            ->toBeTrue()
            ->and($harness->remote($file))
            ->toBeFalse()
            ->and($harness->remote('https://example.com/file.jpg'))
            ->toBeTrue()
            ->and($harness->remote('ABC123_FILE_ID'))
            ->toBeTrue()
            ->and($harness->remote('raw-content', 'report.txt'))
            ->toBeFalse();
    } finally {
        @unlink($file);
    }
});

it('normalizes stream interfaces and resources', function () {
    $harness = new TestMediaInteractionHarness;
    $stream = Utils::streamFor('stream-content');

    expect($harness->normalize($stream))->toBe($stream);

    $resource = fopen('php://memory', 'r+');
    fwrite($resource, 'resource-content');
    rewind($resource);

    $normalized = $harness->normalize($resource);

    expect($normalized->getContents())->toBe('resource-content');

    fclose($resource);
});

it('normalizes readable file paths and raw string uploads', function () {
    $harness = new TestMediaInteractionHarness;
    $file = tempnam(sys_get_temp_dir(), 'telegram-upload-');
    file_put_contents($file, 'file-content');

    try {
        $normalizedFile = $harness->normalize($file);
        $normalizedString = $harness->normalize('raw-content', 'report.txt');

        expect($normalizedFile->getContents())
            ->toBe('file-content')
            ->and($normalizedString->getContents())
            ->toBe('raw-content');
    } finally {
        @unlink($file);
    }
});

it('throws an exception for invalid uploads', function () {
    $harness = new TestMediaInteractionHarness;

    $harness->normalize(['invalid'], null, 'Invalid media input');
})->throws(CouldNotSendNotification::class, 'Invalid file identifier: Invalid media input');
