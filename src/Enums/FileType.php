<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram\Enums;

/**
 * Enum FileType
 *
 * Represents the different file types supported by Telegram Bot API.
 */
enum FileType: string
{
    case Document = 'document';
    case Photo = 'photo';
    case Audio = 'audio';
    case Video = 'video';
    case Animation = 'animation';
    case Voice = 'voice';
    case VideoNote = 'video_note';
    case Sticker = 'sticker';
    case LivePhoto = 'live_photo';

    /**
     * Get all file types as an array.
     *
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }
}
