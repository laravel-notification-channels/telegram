<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use JsonException;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TelegramRichMessageDraft.
 *
 * Builds an `InputRichMessage` for the `sendRichMessageDraft` API method.
 *
 * A draft is a temporary message that is shown to the user while it is still
 * being written. Calling `send()` again with the same `draftId()` animates the
 * replacement of the previously sent content, which makes it a natural fit for
 * streaming an AI generated answer as it is produced:
 *
 * <code>
 * $draft = TelegramRichMessageDraft::create()
 *     ->to($chatId)
 *     ->draftId(1); // Any non-zero integer, stable for this stream.
 *
 * $buffer = '';
 *
 * foreach ($stream as $chunk) {
 *     $buffer .= $chunk;
 *
 *     // Replace the content and push the current state of the answer.
 *     $draft->markdown($buffer)->send();
 * }
 *
 * // Turn the draft into a permanent message via `sendRichMessage`.
 * $draft->markdown($buffer)->finalize();
 * </code>
 *
 * Batch the updates (roughly one call every 0.5-1 seconds) instead of sending
 * one per token, otherwise the bot will quickly hit the API rate limits.
 *
 * Limitations of the `sendRichMessageDraft` endpoint:
 *
 * - Drafts can only be sent to private chats.
 * - Direct file uploads are not supported: reference media by URL or `file_id`.
 * - The `draft_id` must be a non-zero integer.
 * - A draft that is never finalized simply disappears; only `finalize()` (which
 *   calls `sendRichMessage`) leaves a permanent message behind.
 * - Only `chat_id`, `message_thread_id`, `draft_id` and `rich_message` are
 *   accepted by the endpoint. Any other param (buttons, notification flags,
 *   ...) is ignored by Telegram until the draft is finalized.
 *
 * @see https://core.telegram.org/bots/api#sendrichmessagedraft
 */
class TelegramRichMessageDraft extends TelegramRichMessage
{
    public static function create(string $markdown = ''): self
    {
        return new self($markdown);
    }

    /**
     * Set the identifier of the draft.
     *
     * Repeated `send()` calls with the same identifier animate the replacement
     * of the previously sent content.
     *
     * @param  int  $draftId  A non-zero identifier of the draft
     *
     * @throws CouldNotSendNotification When the given identifier is zero
     */
    public function draftId(int $draftId): static
    {
        if ($draftId === 0) {
            throw CouldNotSendNotification::invalidRichMessageDraftId($draftId);
        }

        $this->payload['draft_id'] = $draftId;

        return $this;
    }

    /**
     * Send (or replace) the draft.
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function send(): ?ResponseInterface
    {
        if ($this->getPayloadValue('draft_id') === null) {
            throw CouldNotSendNotification::richMessageDraftIdNotProvided();
        }

        return $this->telegram->sendRichMessageDraft($this->toArray());
    }

    /**
     * Send the current content as a permanent message via `sendRichMessage`.
     *
     * The draft state is left untouched, only the `draft_id` param is dropped
     * from the payload that is sent.
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function finalize(): ?ResponseInterface
    {
        $params = $this->toArray();

        unset($params['draft_id']);

        return $this->telegram->sendRichMessage($params);
    }
}
