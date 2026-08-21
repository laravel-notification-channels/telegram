<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use Illuminate\Support\Facades\View;
use JsonException;
use NotificationChannels\Telegram\Contracts\TelegramSenderContract;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Class TelegramRichMessage.
 *
 * Builds an `InputRichMessage` for the `sendRichMessage` API method.
 *
 * @see https://core.telegram.org/bots/api#sendrichmessage
 *
 * @phpstan-type RichText string|array<mixed>
 * @phpstan-type RichBlock array<string, mixed>
 * @phpstan-type RichMessage array{
 *     blocks?: list<array<string, mixed>>,
 *     html?: string,
 *     markdown?: string,
 *     media?: list<array{id: string, media: array<string, mixed>}>,
 *     is_rtl?: bool,
 *     skip_entity_detection?: bool
 * }
 */
final class TelegramRichMessage extends TelegramBase implements TelegramSenderContract
{
    /** Pattern every `InputRichMessageMedia` identifier must match. */
    private const string MEDIA_ID_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    /** @var RichMessage The `InputRichMessage` being built. */
    protected array $richMessage = [];

    public function __construct(string $markdown = '')
    {
        parent::__construct();

        if ($markdown !== '') {
            $this->markdown($markdown);
        }
    }

    public static function create(string $markdown = ''): self
    {
        return new self($markdown);
    }

    /**
     * Set the Markdown content of the rich message.
     *
     * Media can be referenced with `tg://photo?id=`, `tg://video?id=`
     * and `tg://audio?id=` links.
     */
    public function markdown(string $markdown): self
    {
        $this->richMessage['markdown'] = $markdown;

        return $this;
    }

    /**
     * Set the HTML content of the rich message.
     *
     * Media can be referenced with `tg://photo?id=`, `tg://video?id=`
     * and `tg://audio?id=` links.
     */
    public function html(string $html): self
    {
        $this->richMessage['html'] = $html;

        return $this;
    }

    /**
     * Render a Blade view as the HTML content of the rich message.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->html(View::make($view, $data, $mergeData)->render());
    }

    /**
     * Render the rich message right-to-left.
     */
    public function rtl(bool $rtl = true): self
    {
        $this->richMessage['is_rtl'] = $rtl;

        return $this;
    }

    /**
     * Skip automatic detection of entities such as links and mentions.
     */
    public function skipEntityDetection(bool $skip = true): self
    {
        $this->richMessage['skip_entity_detection'] = $skip;

        return $this;
    }

    /**
     * Attach a media item that can be referenced from the content by its ID.
     *
     * @param  string  $id  1-64 characters, only `A-Z`, `a-z`, `0-9`, `_` and `-`
     * @param  array<string, mixed>  $media  An `InputMediaPhoto`/`Video`/`Animation`/`Audio`/`VoiceNote` array
     *
     * @throws CouldNotSendNotification When the given ID is invalid
     */
    public function media(string $id, array $media): self
    {
        if (preg_match(self::MEDIA_ID_PATTERN, $id) !== 1) {
            throw CouldNotSendNotification::invalidRichMessageMediaId($id);
        }

        $items = $this->richMessage['media'] ?? [];
        $items[] = ['id' => $id, 'media' => $media];

        $this->richMessage['media'] = $items;

        return $this;
    }

    /**
     * Add a paragraph block.
     *
     * @param  RichText  $text
     */
    public function paragraph(string|array $text): self
    {
        return $this->block(['type' => 'paragraph', 'text' => $text]);
    }

    /**
     * Add a heading block.
     *
     * @param  RichText  $text
     */
    public function heading(string|array $text, int $size = 1): self
    {
        return $this->block(['type' => 'heading', 'text' => $text, 'size' => $size]);
    }

    /**
     * Add a preformatted (`pre`) block.
     *
     * @param  RichText  $text
     */
    public function preformatted(string|array $text, ?string $language = null): self
    {
        $block = ['type' => 'pre', 'text' => $text];

        if ($language !== null) {
            $block['language'] = $language;
        }

        return $this->block($block);
    }

    /**
     * Add a footer block.
     *
     * @param  RichText  $text
     */
    public function footer(string|array $text): self
    {
        return $this->block(['type' => 'footer', 'text' => $text]);
    }

    /**
     * Add a divider block.
     */
    public function divider(): self
    {
        return $this->block(['type' => 'divider']);
    }

    /**
     * Add a mathematical expression block.
     */
    public function math(string $expression): self
    {
        return $this->block(['type' => 'mathematical_expression', 'expression' => $expression]);
    }

    /**
     * Add an anchor block that can be linked to.
     */
    public function anchor(string $name): self
    {
        return $this->block(['type' => 'anchor', 'name' => $name]);
    }

    /**
     * Add a blockquote block.
     *
     * A string is wrapped into a single paragraph block.
     *
     * @param  list<array<string, mixed>>|array<string, mixed>|string  $blocks
     * @param  RichText|null  $credit
     */
    public function blockquote(array|string $blocks, string|array|null $credit = null): self
    {
        $block = [
            'type' => 'blockquote',
            'blocks' => is_string($blocks) ? [['type' => 'paragraph', 'text' => $blocks]] : $blocks,
        ];

        if ($credit !== null) {
            $block['credit'] = $credit;
        }

        return $this->block($block);
    }

    /**
     * Add a pullquote block.
     *
     * @param  RichText  $text
     * @param  RichText|null  $credit
     */
    public function pullquote(string|array $text, string|array|null $credit = null): self
    {
        $block = ['type' => 'pullquote', 'text' => $text];

        if ($credit !== null) {
            $block['credit'] = $credit;
        }

        return $this->block($block);
    }

    /**
     * Add a collapsible details block.
     *
     * @param  RichText  $summary
     * @param  list<array<string, mixed>>  $blocks
     */
    public function details(string|array $summary, array $blocks, bool $isOpen = false): self
    {
        return $this->block([
            'type' => 'details',
            'summary' => $summary,
            'blocks' => $blocks,
            'is_open' => $isOpen,
        ]);
    }

    /**
     * Add a table block.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $cells  Rows of `RichBlockTableCell` arrays
     * @param  RichText|null  $caption
     */
    public function table(array $cells, bool $bordered = false, bool $striped = false, string|array|null $caption = null): self
    {
        $block = [
            'type' => 'table',
            'cells' => $cells,
            'is_bordered' => $bordered,
            'is_striped' => $striped,
        ];

        if ($caption !== null) {
            $block['caption'] = $caption;
        }

        return $this->block($block);
    }

    /**
     * Add a list block.
     *
     * Each item may be a string (wrapped into a single paragraph block) or an
     * `InputRichBlockListItem` array.
     *
     * @param  array<int, string|array<string, mixed>>  $items
     */
    public function listBlock(array $items): self
    {
        $items = array_map(
            static fn (string|array $item): array => is_string($item)
                ? ['blocks' => [['type' => 'paragraph', 'text' => $item]]]
                : $item,
            array_values($items)
        );

        return $this->block(['type' => 'list', 'items' => $items]);
    }

    /**
     * Add a thinking block.
     *
     * @param  RichText  $text
     */
    public function thinking(string|array $text): self
    {
        return $this->block(['type' => 'thinking', 'text' => $text]);
    }

    /**
     * Add a map block.
     */
    public function map(float $latitude, float $longitude, int $zoom, int $width, int $height): self
    {
        return $this->block([
            'type' => 'map',
            'location' => ['latitude' => $latitude, 'longitude' => $longitude],
            'zoom' => $zoom,
            'width' => $width,
            'height' => $height,
        ]);
    }

    /**
     * Add a photo block.
     *
     * @param  array<string, mixed>  $photo  An `InputMediaPhoto` array
     * @param  array<string, mixed>|null  $caption  A `RichBlockCaption` array
     */
    public function photoBlock(array $photo, ?array $caption = null): self
    {
        return $this->mediaBlock('photo', $photo, $caption);
    }

    /**
     * Add a video block.
     *
     * @param  array<string, mixed>  $video  An `InputMediaVideo` array
     * @param  array<string, mixed>|null  $caption  A `RichBlockCaption` array
     */
    public function videoBlock(array $video, ?array $caption = null): self
    {
        return $this->mediaBlock('video', $video, $caption);
    }

    /**
     * Add an audio block.
     *
     * @param  array<string, mixed>  $audio  An `InputMediaAudio` array
     * @param  array<string, mixed>|null  $caption  A `RichBlockCaption` array
     */
    public function audioBlock(array $audio, ?array $caption = null): self
    {
        return $this->mediaBlock('audio', $audio, $caption);
    }

    /**
     * Add an animation block.
     *
     * @param  array<string, mixed>  $animation  An `InputMediaAnimation` array
     * @param  array<string, mixed>|null  $caption  A `RichBlockCaption` array
     */
    public function animationBlock(array $animation, ?array $caption = null): self
    {
        return $this->mediaBlock('animation', $animation, $caption);
    }

    /**
     * Add a voice note block.
     *
     * @param  array<string, mixed>  $voiceNote  An `InputMediaVoiceNote` array
     * @param  array<string, mixed>|null  $caption  A `RichBlockCaption` array
     */
    public function voiceNoteBlock(array $voiceNote, ?array $caption = null): self
    {
        return $this->mediaBlock('voice_note', $voiceNote, $caption);
    }

    /**
     * Add a raw `InputRichBlock` array.
     *
     * Useful for block types without a dedicated builder, such as `collage`
     * and `slideshow`.
     *
     * @param  RichBlock  $block
     */
    public function block(array $block): self
    {
        $blocks = $this->richMessage['blocks'] ?? [];
        $blocks[] = $block;

        $this->richMessage['blocks'] = $blocks;

        return $this;
    }

    /**
     * Get the `InputRichMessage` array as built so far.
     *
     * @return RichMessage
     */
    public function getRichMessage(): array
    {
        return $this->richMessage;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            // An empty rich message must still be encoded as a JSON object.
            'rich_message' => json_encode($this->richMessage === [] ? new stdClass : $this->richMessage, JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function send(): ?ResponseInterface
    {
        return $this->telegram->sendRichMessage($this->toArray());
    }

    /**
     * Add a media block of the given type.
     *
     * @param  array<string, mixed>  $media
     * @param  array<string, mixed>|null  $caption
     */
    private function mediaBlock(string $type, array $media, ?array $caption): self
    {
        $block = ['type' => $type, $type => $media];

        if ($caption !== null) {
            $block['caption'] = $caption;
        }

        return $this->block($block);
    }
}
