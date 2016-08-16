# Telegram Notifications Channel for Laravel 5.3 [WIP]

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-notification-channels/telegram.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/telegram)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/laravel-notification-channels/telegram/master.svg?style=flat-square)](https://travis-ci.org/laravel-notification-channels/telegram)
[![StyleCI](https://styleci.io/repos/65490735/shield)](https://styleci.io/repos/65490735)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/d28e31ec-55ce-4306-88a3-84d5d14ad3db.svg?style=flat-square)](https://insight.sensiolabs.com/projects/d28e31ec-55ce-4306-88a3-84d5d14ad3db)
[![Quality Score](https://img.shields.io/scrutinizer/g/laravel-notification-channels/telegram.svg?style=flat-square)](https://scrutinizer-ci.com/g/laravel-notification-channels/telegram)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/laravel-notification-channels/telegram/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/laravel-notification-channels/telegram/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-notification-channels/telegram.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/telegram)

This package makes it easy to send Telegram notification using [Telegram Bot API](https://core.telegram.org/bots) with Laravel 5.3.

## Contents

- [Installation](#installation)
	- [Setting up your Telegram bot](#setting-up-your-telegram-bot)
- [Usage](#usage)
	- [Available Message methods](#available-message-methods)
- [Alternatives](#alternatives)
- [Changelog](#changelog)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

``` bash
composer require laravel-notification-channels/telegram
```

You must install the service provider:

```php
// config/app.php
'providers' => [
    ...
    NotificationChannels\Telegram\TelegramServiceProvider::class,
],
```

## Setting up your Telegram Bot

Talk to [@BotFather](https://core.telegram.org/bots#6-botfather) and generate a Bot API Token.

Then, configure your Telegram Bot API Token:

```php
// config/services.php
...
'telegram-bot-api' => [
    'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE')
],
...
```

## Usage

You can now use the channel in your `via()` method inside the Notification class.

``` php
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification
{
    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {
        $url = url('/invoice/' . $this->invoice->id);

        return TelegramMessage::create()
            ->to($this->user->telegram_user_id) // Optional.
            ->content("*HELLO!* \n One of your invoices has been paid!") // Markdown supported.
            ->button('View Invoice', $url); // Inline Button
    }
}
```

Here's a screenshot preview of the above notification on Telegram Messenger:

![Laravel Telegram Notification Example](https://cloud.githubusercontent.com/assets/1915268/17590374/2e05e872-5ff7-11e6-992f-63d5f3df2db3.png)

### Routing a message

You can either send the notification by providing with the chat id of the recipient to the `to($chatId)` method like shown in the above example or add a `routeNotificationForTelegram()` method in your notifiable model:

``` php
...
/**
 * Route notifications for the Telegram channel.
 *
 * @return int
 */
public function routeNotificationForTelegram()
{
    return $this->telegram_user_id;
}
...
```

### Available Message methods

- `to($chatId)`: (integer) Recipient's chat id.
- `content('')`: (string) Notification message, supports markdown. For more information on supported markdown styles, check out these [docs](https://telegram-bot-sdk.readme.io/docs/sendmessage#section-markdown-style).
- `button($text, $url)`: (string) Adds an inline "Call to Action" button. You can add as many as you want and they'll be placed 2 in a row.
- `options([])`: (array) Allows you to add additional or override `sendMessage` payload (A Telegram Bot API method used to send message internally). For more information on supported parameters, check out these [docs](https://telegram-bot-sdk.readme.io/docs/sendmessage).

## Alternatives

For advance usage, please consider using [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) instead.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email syed@lukonet.com instead of using the issue tracker.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Syed Irfaq R.](https://github.com/irazasyed)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
