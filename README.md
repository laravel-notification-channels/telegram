# Telegram Notifications Channel for Laravel 5.3 [WIP]

[![Author](https://img.shields.io/badge/author-%40iRazaSyed-blue.svg?style=flat-square)][author]
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-notification-channels/telegram.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/telegram)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/laravel-notification-channels/telegram/master.svg?style=flat-square)](https://travis-ci.org/laravel-notification-channels/telegram)
[![StyleCI](https://styleci.io/repos/65490735/shield)](https://styleci.io/repos/65490735)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/d28e31ec-55ce-4306-88a3-84d5d14ad3db.svg?style=flat-square)](https://insight.sensiolabs.com/projects/d28e31ec-55ce-4306-88a3-84d5d14ad3db)
[![Quality Score](https://img.shields.io/scrutinizer/g/laravel-notification-channels/telegram.svg?style=flat-square)](https://scrutinizer-ci.com/g/laravel-notification-channels/telegram)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-notification-channels/telegram.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/telegram)

[![Author](https://cloud.githubusercontent.com/assets/1915268/17607505/cea5dd0e-6043-11e6-98f5-af8f5f878d31.png)][author]

## Overview

> This package makes it easy to send Telegram notification using [Telegram Bot API](https://core.telegram.org/bots) with Laravel 5.3.
>
> For advance usage, Please consider using [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) instead.

## Prerequisites

- Telegram Bot API Token - Talk to [@BotFather](https://core.telegram.org/bots#6-botfather) and generate one.

## Installation

Before you can send notifications via Telegram, you must install the Guzzle HTTP library via Composer:

``` bash
composer require guzzlehttp/guzzle
```

Then, You can install the package via composer:

``` bash
composer require laravel-notification-channels/telegram
```

You must now, install the service provider:
```php
// config/app.php
'providers' => [
    NotificationChannels\Telegram\Provider::class,
];
```

Then, configure your Telegram Bot API Token:

```php
// config/services.php
'telegram-bot-api' => [
    'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE')
]
```

**TIP:** You can also set the token in your `.env` file using the env variable `TELEGRAM_BOT_TOKEN`.

## Usage

You can now use the channel in your `via()` method inside the Notification class.

``` php
use NotificationChannels\Telegram\Channel as TelegramChannel;
use NotificationChannels\Telegram\Message;
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

        return Message::create()
            ->to($this->user->telegram_user_id) // Optional.
            ->content("*HELLO!* \n One of your invoices has been paid!") // Markdown supported.
            ->action('View Invoice', $url); // Inline Button
    }
}
```

Here's a screenshot preview of the above notification on Telegram Messenger:

![Laravel Telegram Notification Example](https://cloud.githubusercontent.com/assets/1915268/17590374/2e05e872-5ff7-11e6-992f-63d5f3df2db3.png)

You can either send the notification by providing with the chat id of the recipient to the `to($chatId)` method like shown in the above example or add a `routeNotificationForTelegram` method in your notifiable model:

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
- `action($text, $url)`: (string) Adds an inline "Call to Action" button.
- `options([])`: (array) Allows you to add additional or override `sendMessage` payload (A Telegram Bot API method used to send message internally). For more information on supported parameters, check out these [docs](https://telegram-bot-sdk.readme.io/docs/sendmessage).

## Security

If you discover any security related issues, please email syed@lukonet.com instead of using the issue tracker.

## Credits

- [Syed Irfaq R.][author]
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[author]: https://github.com/irazasyed
