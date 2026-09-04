# Changelog

All notable changes to `telegram` will be documented in this file

## Unreleased

## 8.0 - 2026-09-04

### What's Changed

#### Added

- Add button style support (danger, success, primary by @kooler62 in https://github.com/laravel-notification-channels/telegram/pull/213
- `TelegramRichMessage` for the `sendRichMessage` API method (Telegram Bot API 10.1/10.2). Supports Markdown / HTML content (`markdown()`, `html()`, `view()`), attached media referenced with `tg://photo?id=` style links (`media()`), the `is_rtl` and `skip_entity_detection` flags, and fluent builders for every documented `InputRichBlock` type (`paragraph()`, `heading()`, `preformatted()`, `footer()`, `divider()`, `math()`, `anchor()`, `blockquote()`, `pullquote()`, `details()`, `table()`, `listBlock()`, `thinking()`, `map()`, `photoBlock()`, `videoBlock()`, `audioBlock()`, `animationBlock()`, `voiceNoteBlock()`) plus a `block()` escape hatch for raw blocks such as `collage` and `slideshow`.
- `TelegramRichMessageDraft` for the `sendRichMessageDraft` API method. Extends `TelegramRichMessage` (so every content and block builder is available) and adds `draftId()`, a `send()` that streams the current content into the draft, and `finalize()` which turns the draft into a permanent message via `sendRichMessage`. Useful for streaming an AI generated answer as it is produced: repeated `send()` calls with the same draft id animate the replacement of the content. Drafts are limited to private chats and the draft id must be non-zero.
- `CouldNotSendNotification::invalidRichMessageDraftId()` and `CouldNotSendNotification::richMessageDraftIdNotProvided()` exception constructors.
- `Telegram::sendRichMessage()` and `Telegram::sendRichMessageDraft()` client methods.
- Optional `style` parameter (`'danger'`, `'success'`, `'primary'`) on `button()`, `buttonWithCallback()`, and `buttonWithWebApp()` methods for colored inline keyboard buttons (Telegram Bot API 9.4).
- Support for `guzzlehttp/guzzle` 8.x alongside 7.x (`^7.8 || ^8.0`).
- `TelegramMessage::escapeLegacyMarkdown()` helper that escapes the characters supported by the legacy `Markdown` parse mode (`_`, `*`, ```, `[`).
- `TelegramFile::fileId()` and `TelegramFile::url()` for attaching an existing Telegram file ID or a remote URL explicitly, bypassing the local-file/URL/file-ID detection heuristics of `file()`. Both validate their input and throw `CouldNotSendNotification::invalidFileIdentifier()` when it is malformed.
- `CouldNotSendNotification::invalidFileType()` exception constructor.
- Default HTTP timeouts on the bundled Guzzle client (`Telegram::DEFAULT_TIMEOUT` = 30s, `Telegram::DEFAULT_CONNECT_TIMEOUT` = 10s), so a network stall can no longer hang a queue worker indefinitely. Configurable via the new `services.telegram.http` config array, which is passed through to the Guzzle client (e.g. `timeout`, `connect_timeout`, `proxy`).
- Automatic retry on rate limiting: when Telegram responds with a 429, the client waits for the reported `parameters.retry_after` and retries once. Responses with a `retry_after` above 60 seconds are not retried and fail immediately.
- `NotificationChannels\Telegram\Facades\Telegram` facade for direct Bot API access (`Telegram::sendMessage([...])` etc.). No alias is auto-registered to avoid clashing with other packages — import the class directly.
- `TelegramUpdates::create()` (and its constructor) accept an optional `Telegram` client instance, so updates can be fetched without resolving the client from the container.

#### Changed

- **Breaking:** `escapedLine()` now escapes according to the currently set parse mode. Under the default legacy `Markdown` mode only `_`, `*`, ``` and `[` are escaped (the only characters that mode supports escaping — previously the full MarkdownV2 set was escaped, which rendered stray backslashes). Under `MarkdownV2` the previous full escaping is applied, and with `HTML` or no parse mode the line is appended untouched. Set the parse mode before calling `escapedLine()`.
  
- **Breaking:** `TelegramFile::file()` (and the string `$type` on the new `fileId()`/`url()`) now throws `CouldNotSendNotification::invalidFileType()` for an unknown file type string instead of silently falling back to `document`.
  
- **Breaking:** `Telegram` client methods (`sendMessage()`, `sendFile()`, `getUpdates()`, etc.) and `TelegramSenderContract::send()` no longer have nullable return types; they either return a PSR-7 response (or a decoded array for chunked messages) or throw. `TelegramUpdates::get()` no longer returns an empty array for a missing response.
  
- **Breaking:** `CouldNotSendNotification::telegramRespondedWithAnError()` now accepts any `GuzzleHttp\Exception\BadResponseException` (previously `ClientException` only) and no longer throws `JsonException` for a non-JSON error body — it falls back to `no description given` instead.
  
- Server errors (5xx) from Telegram are now wrapped by `CouldNotSendNotification::telegramRespondedWithAnError()` with the status code and description, instead of the generic "communication failed" message.
  
- **Breaking:** Removed the unused `FileType::getMimeType()`, `FileType::getAllowedExtensions()`, and `FileType::isExtensionAllowed()` enum helpers. They were never consulted by the package and carried incorrect data (e.g. the non-standard `audio/mp3` MIME type). `FileType::toArray()` remains.
  
- **Breaking:** Array values passed as form params to the `Telegram` client (e.g. via `options()` or direct client calls) are now JSON encoded before sending, as the Bot API expects for structured parameters such as `message_ids`. Previously they were passed to Guzzle's `form_params` and serialized in PHP's `key[0]=...` style, which Telegram rejects. Pre-encoded JSON strings are unaffected.
  
- `onError()` now declares a `static` return type instead of `self`, preserving fluent chain types in subclasses.
  
- Message chunking is now accurate for multibyte text: chunks are measured in UTF-8 characters (Telegram's 4096-character limit) instead of a byte/display-width mix, and the splitter prefers breaking at the last newline, then the last space, before hard-splitting. Note that a split can still land inside a Markdown entity; prefer chunk-sized paragraphs or `normal()` for machine-generated content.
  
- Chunked messages no longer sleep after the final chunk (previously a fixed 1-second delay was added after every chunk, including the last one).
  
- Allowed Pest 5 / PHPUnit 13 for the test suite (`pestphp/pest` and `pestphp/pest-plugin-laravel` `^4.0 || ^5.0`). Pest 5 requires PHP 8.4+ and Laravel 13 (via Testbench 11 / PHPUnit 13), so Pest 4 is kept for the PHP 8.3 and Laravel 12 test matrix. No test or configuration changes were required.
  
- `TelegramRichMessage` is no longer `final` and its fluent builders now return `static` instead of `self`, so subclasses (such as `TelegramRichMessageDraft`) keep chainability.
  
- `Telegram::decodeResponse()` and `CouldNotSendNotification::telegramRespondedWithAnError()` now use PHP's `json_decode()` with `JSON_THROW_ON_ERROR` instead of the removed `GuzzleHttp\Utils::jsonDecode()`. Invalid JSON now throws `JsonException` instead of `GuzzleHttp\Exception\InvalidArgumentException`.
  
- `CouldNotSendNotification::telegramRespondedWithAnError()` no longer calls `ClientException::hasResponse()` (removed in Guzzle 8); a `ClientException` always carries a response in both Guzzle 7 and 8, so the "no response body found" fallback has been dropped.
  

### New Contributors

* @kooler62 made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/213

**Full Changelog**: https://github.com/laravel-notification-channels/telegram/compare/7.0.0...8.0.0

## 7.0 - 2026-03-19

### What's Changed

* Add `TelegramMessage::escapeMarkdown` by @alies-dev in https://github.com/laravel-notification-channels/telegram/pull/209

#### Breaking Changes

- `TelegramFile::file()` now strictly normalizes inputs to PSR-7 streams and rejects unsupported types earlier. Passing arbitrary values through to Guzzle is no longer allowed.

#### Added

- Laravel 13 support.
  
- New Telegram payload builders and fields:
  
  - `businessConnectionId()`, `messageThreadId()`, `directMessagesTopicId()`
  - `protectContent()`, `allowPaidBroadcast()`, `messageEffectId()`
  - `replyParameters()`, `suggestedPostParameters()`
  
- `TelegramLocation` enhancements: `horizontalAccuracy()`, `livePeriod()`, `heading()`, `proximityAlertRadius()`.
  
- `TelegramMessage`: `entities()`, `linkPreviewOptions()`.
  
- `TelegramFile`: `captionEntities()`, `showCaptionAboveMedia()`.
  
- New builders: `TelegramDice`, `TelegramMediaGroup`.
  
- Low-level client helpers:
  
  - `sendDice`, `sendMediaGroup`, `sendChatAction`
  - `editMessageText`, `editMessageCaption`, `editMessageMedia`, `editMessageReplyMarkup`
  - `stopPoll`, `deleteMessage`, `deleteMessages`
  

#### Changed

- Minimum PHP version bumped to 8.3.
  
- Dropped Laravel 11 support.
  
- Codebase now enforces stricter typing (`strict_types`, improved PHPDoc shapes).
  
- Centralized response decoding via `Telegram::decodeResponse()` using Guzzle JSON utilities.
  
- Improved Telegram error parsing with safer fallbacks.
  
- Refactored:
  
  - `TelegramChannel` recipient resolution and response handling
  - `TelegramFile` upload handling (clear remote vs local distinction)
  - `TelegramMessage` chunked sending behavior
  - Shared media logic extracted to `InteractsWithTelegramMedia`
  
- `onError()` now accepts any callable.
  
- `HasSharedLogic` typing hardened; keyboard layouts normalized for invalid column counts.
  
- `TelegramBase` now accepts optional `Telegram` instance (better testability).
  
- `TelegramUpdates::get()` safely returns empty array on invalid responses.
  
- Config now supports both `services.telegram.*` and legacy keys.
  

#### Dev / Tooling

- Upgraded to Pest 4 + `pest-plugin-laravel` 4.
- PHPUnit 12 configuration and coverage updates.
- CI workflows modernized (tests, coverage, changelog, PHPStan, PHP versions).
- PHPStan raised to `level: max` with stricter rules enabled.

#### Tests

- Expanded coverage:
  
  - Response decoding and error parsing
  - Channel routing and early returns
  - Chunked message handling
  - Updates fallback handling
  - Dice, media groups, and client helpers
  
- Updated test suite to align with refactored runtime and tooling.
  

#### Docs

- README refreshed with new builders, helpers, and configuration details.

### New Contributors

* @alies-dev made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/209

**Full Changelog**: https://github.com/laravel-notification-channels/telegram/compare/6.0.0...7.0.0

## 6.0 - 2025-02-25

#### What's Changed

* Add support for Laravel 12.
* Add `TelegramVenue` to support `sendVenue` method.
* Add `sticker` method to the `TelegramFile` to send sticker file.
* Add `sendWhen` method to conditionally send a message.
* Add ParseMode Enum and refactor parsing mode setting logic.
* Add `buttonWithWebApp` method to open web app from a button.
* Add `onError` method to handle exceptions. Based of https://github.com/laravel-notification-channels/telegram/pull/201 by @Hesammousavi.
* Refactor `sendFile` to support raw data sending.
* Refactor `escapedLine` method.
* Refactor `HasSharedLogic` trait.
* Refactor classes to use PHP 8.2 features.
* Revise `keyboard` method parameters to `$requestLocation` and `$requestContact` to be consistent.
* Drop support for Laravel 10.
* Drop support for PHP 8.1.

### New Contributors

* @Hesammousavi made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/201

**Full Changelog**: https://github.com/laravel-notification-channels/telegram/compare/5.0.0...6.0.0

## 5.0 - 2024-03-12

### What's Changed

* Laravel 11 Support.
* Fix Call to a member function toArray() on array by @tantrus332 in https://github.com/laravel-notification-channels/telegram/pull/174
* Add keyboard function to messages by @abbasudo in https://github.com/laravel-notification-channels/telegram/pull/183
* Add ability to change default parsing mode by @abbasudo in https://github.com/laravel-notification-channels/telegram/pull/185
* Addition of 'lineIf' method to messages by @MammutAlex in https://github.com/laravel-notification-channels/telegram/pull/190

### New Contributors

* @tantrus332 made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/174
* @MammutAlex made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/190

**Full Changelog**: https://github.com/laravel-notification-channels/telegram/compare/4.0.0...5.0.0

## 4.0.0 - 2023-02-14

### What's Changed

- Fix Chunk method error by @faissaloux in https://github.com/laravel-notification-channels/telegram/pull/163
- Add escapedLine() method to TelegramMessage by @ziming in https://github.com/laravel-notification-channels/telegram/pull/168
- Laravel 10.x Compatibility by @laravel-shift in https://github.com/laravel-notification-channels/telegram/pull/167
- Drop support for PHP `< 8.1`.

### New Contributors

- @ziming made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/168
- @laravel-shift made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/167

**Full Changelog**: https://github.com/laravel-notification-channels/telegram/compare/3.0.0...4.0.0

## 3.0 - 2022-11-19

### What's Changed

- Send logic moved to drivers by @llabbasmkhll in ### https://github.com/laravel-notification-channels/telegram/pull/146
- Drop support for Laravel < 9.
- Convert tests to Pest and improve coverage in https://github.com/laravel-notification-channels/telegram/pull/151.
- Add TelegramUpdatesTests.
- Add PHPStan for static analysis using GitHub Action.
- Add Changelog updater workflow.
- Add Codecov for code coverage workflow.
- Improve GitHub action workflows.
- Clean and improve code.
- Remove the scrutinizer.
- Add `line()` method (adapted from a PR) by @llabbasmkhll in https://github.com/laravel-notification-channels/telegram/pull/149.
- Upgrade required and dev packages.
- Normalize composer JSON file.
- Add type hints and return types were supported.
- Rename `TelegramSender` contract to `TelegramSenderContract`
- Harden error handling for JSON encode and decode methods.
- Improve doc blocks.
- Add `toArray()` method to TelegramUpdates to retrieve payload.
- Restructure the tests directory.
- Add data to notification failed event in https://github.com/laravel-notification-channels/telegram/pull/156
- Add More Tests in https://github.com/laravel-notification-channels/telegram/pull/157

### New Contributors

- @llabbasmkhll made their first contribution in https://github.com/laravel-notification-channels/telegram/pull/146

**Full Changelog**: https://github.com/laravel-notification-channels/telegram/compare/2.1.0...3.0.0

## 2.0.0 - 2022-02-11

- Add Laravel 9 Support.
- Add Laravel conditional trait to payload builder to use `when` on methods chain. PR [#139](https://github.com/laravel-notification-channels/telegram/pull/139).
- Drop support for older version of PHP `< 7.4` and Laravel `< 8`.

## 1.0.0 - 2021-12-11

- Register Telegram instance to container.
- Add `TelegramUpdates` to retrieve bot updates. PR [#133](https://github.com/laravel-notification-channels/telegram/pull/133).
- Refactor TelegramChannel. PR [#136](https://github.com/laravel-notification-channels/telegram/pull/136).
- Add Retrieving Chat ID docs and improve docs.
- Add missing type declaration and minor improvements to various methods.
- Add Contact Support. PR [#138](https://github.com/laravel-notification-channels/telegram/pull/138).

## 0.9.0 - 2021-11-24

- Add Poll Support. PR [#130](https://github.com/laravel-notification-channels/telegram/pull/130).
- Remove StyleCI in favor of GitHub Actions Workflow for Code Styling. PR [#131](https://github.com/laravel-notification-channels/telegram/pull/131).

## 0.8.0 - 2021-11-14

- Add message chunking feature (`chunk($limit)`) in cases where the message is too long. Closes [#127](https://github.com/laravel-notification-channels/telegram/issues/127).

## 0.7.0 - 2021-10-28

- Dropped PHP 7.1 support. PR [#118](https://github.com/laravel-notification-channels/telegram/pull/118).
- Dispatch event `NotificationFailed` on exception. PR [#119](https://github.com/laravel-notification-channels/telegram/pull/119).
- Test against PHP 8.1. PR [#120](https://github.com/laravel-notification-channels/telegram/pull/120).
- Add support to use `TelegramChannel::class` in on-demand notification route. PR [#122](https://github.com/laravel-notification-channels/telegram/pull/122).
- Refactor channel registration with the channel manager. PR [#122](https://github.com/laravel-notification-channels/telegram/pull/122).

## 0.6.0 - 2021-10-04

- Add GitHub Actions workflows for tests and coverage. PR [#103](https://github.com/laravel-notification-channels/telegram/pull/103).
- Add alternate method to resolve Telegram notification channel. PR [#110](https://github.com/laravel-notification-channels/telegram/pull/110).
- Add `buttonWithCallback()` method. PR [#114](https://github.com/laravel-notification-channels/telegram/pull/114).
- Revise file upload logic.
- Add more info on proxy setting.
- Remove dead badges.

## 0.5.1 - 2020-12-07

- PHP 8 Support.

## 0.5.0 - 2020-09-08

- Add previous `ClientException` when constructing `CouldNotSendNotification` exception. PR [#86](https://github.com/laravel-notification-channels/telegram/pull/86).
- Add Laravel 8 Support. PR [#88](https://github.com/laravel-notification-channels/telegram/pull/88).
- Add Bot token per notification support. Closed [#84](https://github.com/laravel-notification-channels/telegram/issues/84).
- Add view file support for notification content. Closed [#82](https://github.com/laravel-notification-channels/telegram/issues/82).
- Add support to set HTTP Client.

## 0.4.1 - 2020-07-07

- Add Guzzle 7 Support. PR [#80](https://github.com/laravel-notification-channels/telegram/pull/80).

## 0.4.0 - 2020-06-02

- Add support to set custom api `base_uri` for web bridge.
- Revise README with instructions for Proxy or Bridge support.
- Revise on-demand notification instructions - Fixes [#72](https://github.com/laravel-notification-channels/telegram/issues/72).
- Fix typo in test.
- Remove redundant test.
- Remove exception when chat id isn't provided - PR [#75](https://github.com/laravel-notification-channels/telegram/pull/75).

## 0.3.0 - 2020-03-26

- Add ability to set param in `disableNotification` method.

## 0.2.0 - 2020-02-19

- Laravel 7 Support.
- Support response handling from Telegram.

## 0.1.1 - 2019-11-07

- Support PHP 7.1 and up.

## 0.1.0 - 2019-10-11

- New Helper Methods to work with file attachments.
- Code cleanup.
- Documentation updated with more examples and previews.
- Micro optimization and improvements.
- Typehint and return type declaration.
- Fixed tests.

## 0.0.6 - 2019-09-28

- Laravel 6 Support.
- Add Photo, Document, Audio, Location and other file notification type support.
- Token getter and setter.

## 0.0.5 - 2018-09-08

- Laravel 5.7 Support.
- Add ability to change button columns.

## 0.0.4 - 2018-02-08

- Laravel 5.6 Support.

## 0.0.3 - 2017-09-01

- Laravel 5.5 Support with Auto-Discovery.

## 0.0.2 - 2017-03-24

- Laravel 5.4 Support.

## 0.0.1 - 2016-08-14

- Initial Release.
