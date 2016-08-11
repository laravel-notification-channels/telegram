# Telegram Notification Channel for Laravel 5.3 [WIP]

> This package makes it easy to send Telegram notification using Telegram Bot API with Laravel 5.3.
>
> For advance usage, Please consider using [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) instead.

## Prerequisites

- Telegram Bot API Token - Talk to [@BotFather](https://core.telegram.org/bots#botfather) and generate one.

## Installation

Before you can send notifications via Telegram, you must install the Guzzle HTTP library via Composer:

``` bash
composer require guzzlehttp/guzzle
```

You can install the package via composer:

``` bash
composer require laravel-notification-channels/telegram
```

You must install the service provider:
```php
// config/app.php
'providers' => [
    NotificationChannels\Telegram\Provider::class,
];
```

Set the Telegram Bot API Token:

```php
// config/services.php
'telegram-bot-api' => [
    'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE')
]
```

You can also set the token in your `.env` file using the env variable `TELEGRAM_BOT_TOKEN`.

## Usage

(Optional) Now, Add a `routeNotificationForTelegram` method in your `App\User` model.

``` php
<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Route notifications for the Telegram channel.
     *
     * @return string
     */
    public function routeNotificationForTelegram()
    {
        return $this->telegram_user_id;
    }
}
```

You can now use the channel in your `via()` method inside the notification as well as send a Telegram notification:

``` php
<?php

namespace App\Notifications;

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

        return (new Message())
            ->content("*HELLO!* \n One of your invoices has been paid!")
            ->action('View Invoice', $url);
    }
}
```

Here's a screenshot preview of the above notification on Telegram Messenger:

![Laravel Telegram Notification Example](https://cloud.githubusercontent.com/assets/1915268/17590374/2e05e872-5ff7-11e6-992f-63d5f3df2db3.png)

You can also override the recipient for this notification by passing a chat id to the method `to($chatId)` in your `toTelegram()` method above.

Here's an example of that:

```php
public function toTelegram($notifiable)
{
    $url = url('/invoice/' . $this->invoice->id);

    return (new Message())
        ->to($this->user->telegram_user_id)
        ->content("One of your invoices has been paid!")
        ->action('View Invoice', $url);
}
```

The content supports markdown, please refer these [docs](https://telegram-bot-sdk.readme.io/docs/sendmessage#section-markdown-style) for supported formating style.

(Advance) The `Message` class also has an `options()` method which can be used to add additional or override parameters, refer these [docs](https://telegram-bot-sdk.readme.io/docs/sendmessage) to know more about other parameters.

## Credits

- [Syed Irfaq R.](https://github.com/irazasyed)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
