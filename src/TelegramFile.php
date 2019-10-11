<?php

namespace NotificationChannels\Telegram;

use JsonSerializable;
use NotificationChannels\Telegram\Traits\HasSharedLogic;

/**
 * Class TelegramFile.
 */
class TelegramFile implements JsonSerializable
{
    use HasSharedLogic;

    /** @var string content type. */
    public $type = 'document';

    /**
     * @param string $content
     *
     * @return self
     */
    public static function create($content = ''): self
    {
        return new self($content);
    }

    /**
     * Message constructor.
     *
     * @param string $content
     */
    public function __construct($content = '')
    {
        $this->content($content);
        $this->payload['parse_mode'] = 'Markdown';
    }

    /**
     * Notification message (Supports Markdown).
     *
     * @param $content
     *
     * @return $this
     */
    public function content($content): self
    {
        $this->payload['caption'] = $content;

        return $this;
    }

    /**
     * Add File to Message.
     *
     * Generic method to attach files of any type based on API.
     *
     * @param string      $file
     * @param string      $type
     * @param string|null $filename
     *
     * @return $this
     */
    public function file($file, string $type, string $filename = null): self
    {
        $this->type = $type;

        $this->payload['file'] = [
            'name'     => $type,
            'contents' => (is_file($file) && is_readable($file)) ? fopen($file, 'rb') : $file,
        ];

        if ($filename !== null) {
            $this->payload['file']['filename'] = $filename;
        }

        return $this;
    }

    /**
     * Attach an image.
     *
     * Use this method to send photos.
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function photo(string $file, string $filename = null): self
    {
        return $this->file($file, 'photo', $filename);
    }

    /**
     * Attach an audio file.
     *
     * Use this method to send audio files, if you want Telegram clients to display them in the music player.
     * Your audio must be in the .mp3 format.
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function audio(string $file, string $filename = null): self
    {
        return $this->file($file, 'audio', $filename);
    }

    /**
     * Attach a document or any file as document.
     *
     * Use this method to send general files.
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function document(string $file, string $filename = null): self
    {
        return $this->file($file, 'document', $filename);
    }

    /**
     * Attach a video file.
     *
     * Use this method to send video files, Telegram clients support mp4 videos.
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function video(string $file, string $filename = null): self
    {
        return $this->file($file, 'video', $filename);
    }

    /**
     * Attach an animation file.
     *
     * Use this method to send animation files (GIF or H.264/MPEG-4 AVC video without sound).
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function animation(string $file, string $filename = null): self
    {
        return $this->file($file, 'animation', $filename);
    }

    /**
     * Attach a voice file.
     *
     * Use this method to send audio files, if you want Telegram clients to display the file as a playable voice
     * message. For this to work, your audio must be in an .ogg file encoded with OPUS.
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function voice(string $file, string $filename = null): self
    {
        return $this->file($file, 'voice', $filename);
    }

    /**
     * Attach a video note file.
     *
     * Telegram clients support rounded square mp4 videos of up to 1 minute long.
     * Use this method to send video messages.
     *
     * @param string      $file
     * @param string|null $filename
     *
     * @return $this
     */
    public function videoNote(string $file, string $filename = null): self
    {
        return $this->file($file, 'video_note', $filename);
    }

    /**
     * Create Multipart array.
     *
     * @return array
     */
    public function toMultipart(): array
    {
        $data = [];
        foreach ($this->payload as $name => $contents) {
            $data[] = ($name === 'file') ? $contents : compact('name', 'contents');
        }

        return $data;
    }
}
