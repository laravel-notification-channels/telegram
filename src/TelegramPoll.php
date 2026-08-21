<?php

declare(strict_types=1);

namespace NotificationChannels\Telegram;

use JsonException;
use NotificationChannels\Telegram\Contracts\TelegramSenderContract;
use NotificationChannels\Telegram\Enums\ParseMode;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TelegramPoll.
 */
class TelegramPoll extends TelegramBase implements TelegramSenderContract
{
    public function __construct(string $question = '')
    {
        parent::__construct();
        $this->question($question);
    }

    public static function create(string $question = ''): self
    {
        return new self($question);
    }

    /**
     * Poll question.
     *
     * @return $this
     */
    public function question(string $question): self
    {
        $this->payload['question'] = $question;

        return $this;
    }

    /**
     * Poll choices.
     *
     * Each choice may either be a plain string or an `InputPollOption` array
     * such as `['text' => 'Yes', 'media' => [...]]`.
     *
     * @param  array<int, string|array<string, mixed>>  $choices
     *
     * @throws JsonException
     */
    public function choices(array $choices): self
    {
        $options = array_map(
            static fn (string|array $choice): array => is_string($choice) ? ['text' => $choice] : $choice,
            array_values($choices)
        );

        return $this->jsonPayload('options', $options);
    }

    /**
     * Poll description.
     *
     * @return $this
     */
    public function description(string $description): self
    {
        $this->payload['description'] = $description;

        return $this;
    }

    /**
     * Set the parse mode of the poll description.
     *
     * @return $this
     */
    public function descriptionParseMode(ParseMode|string $mode): self
    {
        $this->payload['description_parse_mode'] = ($mode instanceof ParseMode) ? $mode->value : $mode;

        return $this;
    }

    /**
     * Set explicit description entities instead of using a parse mode.
     *
     * @param  array<int, array<string, mixed>>  $entities
     *
     * @throws JsonException
     */
    public function descriptionEntities(array $entities): self
    {
        return $this->jsonPayload('description_entities', $entities);
    }

    /**
     * Text shown when a user chooses an incorrect answer or taps the lamp icon in a quiz poll.
     *
     * @return $this
     */
    public function explanation(string $explanation): self
    {
        $this->payload['explanation'] = $explanation;

        return $this;
    }

    /**
     * Set the parse mode of the poll explanation.
     *
     * @return $this
     */
    public function explanationParseMode(ParseMode|string $mode): self
    {
        $this->payload['explanation_parse_mode'] = ($mode instanceof ParseMode) ? $mode->value : $mode;

        return $this;
    }

    /**
     * Set explicit explanation entities instead of using a parse mode.
     *
     * @param  array<int, array<string, mixed>>  $entities
     *
     * @throws JsonException
     */
    public function explanationEntities(array $entities): self
    {
        return $this->jsonPayload('explanation_entities', $entities);
    }

    /**
     * Poll type, currently `regular` or `quiz`.
     *
     * @return $this
     */
    public function type(string $type): self
    {
        $this->payload['type'] = $type;

        return $this;
    }

    /**
     * Turn the poll into a quiz with the given correct choice(s).
     *
     * @param  int|array<int, int>  $correctOptionIds  Zero based index(es) of the correct choice(s)
     *
     * @throws JsonException
     */
    public function quiz(int|array $correctOptionIds): self
    {
        $correctOptionIds = is_int($correctOptionIds) ? [$correctOptionIds] : array_values($correctOptionIds);

        return $this->type('quiz')
            ->jsonPayload('correct_option_ids', $correctOptionIds);
    }

    /**
     * Allow users to change their vote.
     *
     * @return $this
     */
    public function allowsRevoting(bool $allow = true): self
    {
        $this->payload['allows_revoting'] = $allow;

        return $this;
    }

    /**
     * Shuffle the order of the poll choices for each user.
     *
     * @return $this
     */
    public function shuffleOptions(bool $shuffle = true): self
    {
        $this->payload['shuffle_options'] = $shuffle;

        return $this;
    }

    /**
     * Allow users to add their own choices to the poll.
     *
     * @return $this
     */
    public function allowAddingOptions(bool $allow = true): self
    {
        $this->payload['allow_adding_options'] = $allow;

        return $this;
    }

    /**
     * Hide the poll results until the poll is closed.
     *
     * @return $this
     */
    public function hideResultsUntilCloses(bool $hide = true): self
    {
        $this->payload['hide_results_until_closes'] = $hide;

        return $this;
    }

    /**
     * Restrict voting to the members of the chat.
     *
     * @return $this
     */
    public function membersOnly(bool $membersOnly = true): self
    {
        $this->payload['members_only'] = $membersOnly;

        return $this;
    }

    /**
     * Restrict voting to users from the given two letter country codes.
     *
     * @param  array<int, string>  $codes
     *
     * @throws JsonException
     */
    public function countryCodes(array $codes): self
    {
        return $this->jsonPayload('country_codes', array_values($codes));
    }

    /**
     * Make the poll anonymous.
     *
     * @return $this
     */
    public function isAnonymous(bool $anonymous = true): self
    {
        $this->payload['is_anonymous'] = $anonymous;

        return $this;
    }

    /**
     * Allow multiple answers in the poll.
     *
     * @return $this
     */
    public function allowsMultipleAnswers(bool $allow = true): self
    {
        $this->payload['allows_multiple_answers'] = $allow;

        return $this;
    }

    /**
     * Amount of time in seconds the poll will be active after creation.
     *
     * @return $this
     */
    public function openPeriod(int $seconds): self
    {
        $this->payload['open_period'] = $seconds;

        return $this;
    }

    /**
     * Point in time (Unix timestamp) when the poll will be automatically closed.
     *
     * @return $this
     */
    public function closeDate(int $timestamp): self
    {
        $this->payload['close_date'] = $timestamp;

        return $this;
    }

    /**
     * Media to be shown alongside the poll question.
     *
     * @param  array<string, mixed>  $media  An `InputPollMedia` array
     *
     * @throws JsonException
     */
    public function media(array $media): self
    {
        return $this->jsonPayload('media', $media);
    }

    /**
     * Media to be shown alongside the quiz explanation.
     *
     * @param  array<string, mixed>  $media  An `InputPollMedia` array
     *
     * @throws JsonException
     */
    public function explanationMedia(array $media): self
    {
        return $this->jsonPayload('explanation_media', $media);
    }

    /**
     * @throws CouldNotSendNotification
     */
    public function send(): ?ResponseInterface
    {
        return $this->telegram->sendPoll($this->toArray());
    }
}
