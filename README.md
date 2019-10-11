# Telegram Notifications Channel for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-notification-channels/telegram.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/telegram)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/d28e31ec-55ce-4306-88a3-84d5d14ad3db.svg?style=flat-square)](https://insight.sensiolabs.com/projects/d28e31ec-55ce-4306-88a3-84d5d14ad3db)
[![Quality Score](https://img.shields.io/scrutinizer/g/laravel-notification-channels/telegram.svg?style=flat-square)](https://scrutinizer-ci.com/g/laravel-notification-channels/telegram)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-notification-channels/telegram.svg?style=flat-square)](https://packagist.org/packages/laravel-notification-channels/telegram)

This package makes it easy to send Telegram notification using [Telegram Bot API](https://core.telegram.org/bots) with Laravel.

## Contents

- [Installation](#installation)
  - [Setting up your Telegram bot](#setting-up-your-telegram-bot)
- [Usage](#usage)
  - [Text Notification](#text-notification)
  - [Attach a Photo](#attach-a-photo)
  - [Attach a Document](#attach-a-document)
  - [Attach a Location](#attach-a-location)
  - [Attach a Video](#attach-a-video)
  - [Attach a Gif File](#attach-a-gif-file)
  - [Available Message methods](#available-message-methods)
  - [Available Location methods](#available-location-methods)
  - [Available File methods](#available-file-methods)
- [Alternatives](#alternatives)
- [Changelog](#changelog)
- [Testing](#testing)
- [Security](#security)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require laravel-notification-channels/telegram
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

### Text Notification

```php
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
            // Optional recipient user id.
            ->to($notifiable->telegram_user_id)
            // Markdown supported.
            ->content("Hello there!\nYour invoice has been *PAID*")
            // (Optional) Inline Buttons
            ->button('View Invoice', $url)
            ->button('Download Invoice', $url);
    }
}
```

Here's a screenshot preview of the above notification on Telegram Messenger:

![Laravel Telegram Notification Example](https://user-images.githubusercontent.com/1915268/66616627-39be6180-ebef-11e9-92cc-f2da81da047a.jpg)

### Attach a Photo

```php
public function toTelegram($notifiable)
{
    $url = url('/file/' . $this->file->id);

    return TelegramFile::create()
        ->to($notifiable->telegram_user_id)
        ->content('Awesome *bold* text and [inline URL](http://www.example.com/)')
        ->file('/storage/archive/6029014.jpg', 'photo'); // local photo

        // OR using a helper method with remote file.
        // ->photo('https://file-examples.com/wp-content/uploads/2017/10/file_example_JPG_1MB.jpg');
}
```

Preview:

![Laravel Telegram Photo Notification Example](https://user-images.githubusercontent.com/1915268/66616792-daad1c80-ebef-11e9-9bdf-c0bc484cf037.jpg)

### Attach a Document

```php
public function toTelegram($notifiable)
{
    $url = url('/file/' . $this->file->id);

    return TelegramFile::create()
        ->to($notifiable->telegram_user_id)
        ->content('Did you know we can set a custom filename too?')
        ->document('https://file-examples.com/wp-content/uploads/2017/10/file-sample_150kB.pdf', 'sample.pdf');
}
```

Preview:

![Laravel Telegram Document Notification Example](https://user-images.githubusercontent.com/1915268/66616850-10520580-ebf0-11e9-9122-4f4d263f3b53.jpg)

### Attach a Location

```php
public function toTelegram($notifiable)
{
    return TelegramLocation::create()
        ->to($notifiable->telegram_user_id)
        ->latitude('40.6892494')
        ->longitude('-74.0466891');
}
```

Preview:

![Laravel Telegram Location Notification Example](https://user-images.githubusercontent.com/1915268/66616918-54450a80-ebf0-11e9-86ea-d5264fe05ba9.jpg)

### Attach a Video

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->to($notifiable->telegram_user_id)
        ->content('Sample *video* notification!')
        ->video('https://file-examples.com/wp-content/uploads/2017/04/file_example_MP4_480_1_5MG.mp4');
}
```

Preview:

![Laravel Telegram Video Notification Example](https://user-images.githubusercontent.com/1915268/66617038-ed742100-ebf0-11e9-865a-bf0245d2cbbb.jpg)

### Attach a Gif File

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->content('Woot! We can send animated gif notifications too!')
        ->animation('https://sample-videos.com/gif/2.gif');

        // Or local file
        // ->animation('/path/to/some/animated.gif');
}
```

Preview:

![Laravel Telegram Gif Notification Example](https://user-images.githubusercontent.com/1915268/66617071-109ed080-ebf1-11e9-989b-b237f2b9502d.jpg)

### Routing a message

You can either send the notification by providing with the chat id of the recipient to the `to($chatId)` method like shown in the above example or add a `routeNotificationForTelegram()` method in your notifiable model:

```php
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
- `content('')`: (string) Notification message, supports markdown. For more information on supported markdown styles, check out these [docs](https://telegram-bot-sdk.readme.io/reference#section-formatting-options).
- `button($text, $url)`: (string) Adds an inline "Call to Action" button. You can add as many as you want and they'll be placed 2 in a row.
- `disableNotification()`: Send the message silently.  Users will receive a notification with no sound.
- `options([])`: (array) Allows you to add additional or override `sendMessage` payload (A Telegram Bot API method used to send message internally). For more information on supported parameters, check out these [docs](https://telegram-bot-sdk.readme.io/docs/sendmessage).

### Available Location methods

- `to($chatId)`: (integer) Recipient's chat id.
- `latitude($latitude)`: (float|string) Latitude of the location.
- `longitude($longitude)`: (float|string) Longitude of the location.
- `button($text, $url)`: (string) Adds an inline "Call to Action" button. You can add as many as you want and they'll be placed 2 in a row.
- `disableNotification()`: Send the message silently. Users will receive a notification with no sound.
- `options([])`: (array) Allows you to add additional or override the payload.

### Available File methods

- `to($chatId)`: (integer) Recipient's chat id.
- `content('')`: (string) File caption, supports markdown. For more information on supported markdown styles, check out these [docs](https://telegram-bot-sdk.readme.io/reference#section-formatting-options).
- `file($file, $type, $filename = null)`: Local file path or remote URL, `$type` of the file (Ex:`photo`, `audio`, `document`, `video`, `animation`, `voice`, `video_note_`) and optionally filename with extension. Ex: `sample.pdf`. You can use helper methods instead of using this to make it easier to work with file attachment.
- `photo($file, $filename = null)`: Helper method to attach a photo.
- `audio($file, $filename = null)`: Helper method to attach an audio file (MP3 file).
- `document($file, $filename = null)`: Helper method to attach a document or any file as document.
- `video($file, $filename = null)`: Helper method to attach a video file.
- `animation($file, $filename = null)`: Helper method to attach an animated gif file.
- `voice($file, $filename = null)`: Helper method to attach a voice note (`.ogg` file with OPUS encoded).
- `videoNote($file, $filename = null)`: Helper method to attach a video note file (Upto 1 min long, rounded square video).
- `button($text, $url)`: (string) Adds an inline "Call to Action" button. You can add as many as you want and they'll be placed 2 in a row.
- `disableNotification()`: Send the message silently. Users will receive a notification with no sound.
- `options([])`: (array) Allows you to add additional or override the payload.

## Alternatives

For advance usage, please consider using [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) instead.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

```bash
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
