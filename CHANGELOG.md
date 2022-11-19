# Changelog

All notable changes to `telegram` will be documented in this file

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
