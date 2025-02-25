<?php

namespace NotificationChannels\Telegram;

use Illuminate\Support\Facades\View;
use NotificationChannels\Telegram\Contracts\TelegramSenderContract;
use NotificationChannels\Telegram\Enums\FileType;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class TelegramFile
 *
 * Handles file-based Telegram notifications with support for various file types
 * and content formats.
 */
class TelegramFile extends TelegramBase implements TelegramSenderContract
{
    /** @var FileType The file content type */
    public FileType $type = FileType::Document;

    /** @var array File types that don't support captions */
    protected array $captionUnsupportedTypes = [
        FileType::VideoNote,
        FileType::Sticker,
    ];

    /**
     * Create a new TelegramFile instance.
     */
    public function __construct(string $content = '')
    {
        parent::__construct();
        $this->content($content);
        $this->parseMode(ParseMode::Markdown);
    }

    /**
     * Create a new instance of TelegramFile.
     */
    public static function create(string $content = ''): self
    {
        return new self($content);
    }

    /**
     * Set notification caption for supported file types with markdown support.
     */
    public function content(string $content): self
    {
        $this->payload['caption'] = $content;

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  resource|StreamInterface|string  $file  The file content or path
     * @param  FileType|string  $type  The file type
     * @param  string|null  $filename  Optional custom filename
     *
     * @throws CouldNotSendNotification
     */
    public function file(mixed $file, FileType|string $type, ?string $filename = null): self
    {
        $this->type = is_string($type) ? FileType::tryFrom($type) ?? FileType::Document : $type;
        $typeValue = $this->type->value;

        // Handle file URLs or Telegram file IDs
        if (is_string($file) && ! $this->isReadableFile($file) && $filename === null) {
            if (! filter_var($file, FILTER_VALIDATE_URL) && ! preg_match('/^[a-zA-Z0-9_-]+$/', $file)) {
                throw CouldNotSendNotification::invalidFileIdentifier($file);
            }

            $this->payload[$typeValue] = $file;

            return $this;
        }

        $contents = match (true) {
            $file instanceof StreamInterface => $file->detach(),
            is_resource($file) => $file,
            $this->isReadableFile($file) => @fopen($file, 'rb') ?: throw CouldNotSendNotification::fileAccessFailed($file),
            default => $file
        };

        $fileData = [
            'name' => $typeValue,
            'contents' => $contents,
        ];

        if ($filename !== null) {
            $fileData['filename'] = $filename;
        }

        $this->payload['file'] = $fileData;

        return $this;
    }

    /**
     * Attach a photo.
     */
    public function photo(string $file): self
    {
        return $this->file($file, FileType::Photo);
    }

    /**
     * Attach an audio file.
     */
    public function audio(string $file): self
    {
        return $this->file($file, FileType::Audio);
    }

    /**
     * Attach a document file.
     */
    public function document(string $file, ?string $filename = null): self
    {
        return $this->file($file, FileType::Document, $filename);
    }

    /**
     * Attach a video file.
     */
    public function video(string $file): self
    {
        return $this->file($file, FileType::Video);
    }

    /**
     * Attach an animation file.
     */
    public function animation(string $file): self
    {
        return $this->file($file, FileType::Animation);
    }

    /**
     * Attach a voice message file.
     */
    public function voice(string $file): self
    {
        return $this->file($file, FileType::Voice);
    }

    /**
     * Attach a video note file.
     */
    public function videoNote(string $file): self
    {
        return $this->file($file, FileType::VideoNote);
    }

    /**
     * Attach a sticker.
     */
    public function sticker(string $file): self
    {
        return $this->file($file, FileType::Sticker);
    }

    /**
     * Use a Laravel Blade view as the content.
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->content(View::make($view, $data, $mergeData)->render());
    }

    /**
     * Check if a file is attached.
     */
    public function hasFile(): bool
    {
        return isset($this->payload['file']);
    }

    /**
     * Check if the current file type supports captions.
     */
    protected function supportsCaptions(): bool
    {
        return ! in_array($this->type, $this->captionUnsupportedTypes);
    }

    /**
     * Convert the notification to an array for API consumption.
     */
    public function toArray(): array
    {
        $payload = $this->payload;

        // Remove caption for unsupported file types
        if (! $this->supportsCaptions() && isset($payload['caption'])) {
            unset($payload['caption']);
        }

        return $this->hasFile() ? $this->toMultipart($payload) : $payload;
    }

    /**
     * Create multipart array for file uploads.
     */
    public function toMultipart(?array $payload = null): array
    {
        $payload = $payload ?? $this->payload;
        $data = [];

        foreach ($payload as $name => $contents) {
            $data[] = ($name === 'file')
                ? $contents
                : compact('name', 'contents');
        }

        return $data;
    }

    /**
     * Send the notification through Telegram.
     *
     * @throws CouldNotSendNotification
     */
    public function send(): ?ResponseInterface
    {
        // Get the method endpoint based on file type
        $method = $this->type->value;

        return $this->telegram->sendFile(
            $this->toArray(),
            $method,
            $this->hasFile()
        );
    }

    /**
     * Determine if it's a regular and readable file.
     */
    protected function isReadableFile(string $file): bool
    {
        return is_file($file) && is_readable($file);
    }
}
