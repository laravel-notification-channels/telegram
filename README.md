# Telegram Notifications Channel for Laravel

[![Join PHP Chat][ico-phpchat]][link-phpchat]
[![Chat on Telegram][ico-telegram]][link-telegram]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-packagist]

This package makes it easy to send Telegram notification using [Telegram Bot API](https://core.telegram.org/bots) with Laravel.

## Contents

- [Installation](#installation)
  - [Setting up your Telegram Bot](#setting-up-your-telegram-bot)
  - [Retrieving Chat ID](#retrieving-chat-id)
  - [Using in Lumen](#using-in-lumen)
  - [Proxy or Bridge Support](#proxy-or-bridge-support)
- [Usage](#usage)
  - [Text Notification](#text-notification)
  - [Send with Keyboard](#send-with-keyboard)
  - [Send a Poll](#send-a-poll)
  - [Attach a Contact](#attach-a-contact)
  - [Attach an Audio](#attach-an-audio)
  - [Attach a Photo](#attach-a-photo)
  - [Attach a Document](#attach-a-document)
  - [Attach a Location](#attach-a-location)
  - [Attach a Venue](#attach-a-venue)
  - [Attach a Video](#attach-a-video)
  - [Attach a GIF File](#attach-a-gif-file)
  - [Attach a Sticker](#attach-a-sticker)
  - [Routing a Message](#routing-a-message)
  - [Handling Response](#handling-response)
  - [Exception Handling](#exception-handling)
    - [Using NotificationFailed Event](#using-notificationfailed-event)
    - [Using onError Callback](#using-onerror-callback)
  - [On-Demand Notifications](#on-demand-notifications)
  - [Sending to Multiple Recipients](#sending-to-multiple-recipients)
- [Available Methods](#available-methods)
  - [Common Methods](#common-methods)
  - [Telegram Message Methods](#telegram-message-methods)
  - [Telegram Location Methods](#telegram-location-methods)
  - [Telegram Venue Methods](#telegram-venue-methods)
  - [Telegram File Methods](#telegram-file-methods)
  - [Telegram Contact Methods](#telegram-contact-methods)
  - [Telegram Poll Methods](#telegram-poll-methods)
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
# config/services.php

'telegram-bot-api' => [
    'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE')
],
```

## Retrieving Chat ID

For us to send notifications to your Telegram Bot user/channel or group, we need to know their Chat ID.

This can be done by fetching the [updates][link-telegram-docs-update] for your Bot using the `getUpdates` method as per Telegram Bot API [docs][link-telegram-docs-getupdates].

An [update][link-telegram-docs-update] is an object containing relevant fields based on the type of update it represents, some examples of an update object are `message`, `callback_query`, and `poll`. For a complete list of fields, see [Telegram Bot API docs][link-telegram-docs-update].

To make things easier, the library comes with a handy method that can be used to get the updates from which you can parse the relevant Chat ID.

Please keep in mind the user has to first interact with your bot for you to be able to obtain their Chat ID which you can then store in your database for future interactions or notifications.

Here's an example of fetching an update:

```php
use NotificationChannels\Telegram\TelegramUpdates;

// Response is an array of updates.
$updates = TelegramUpdates::create()

    // (Optional). Get's the latest update.
    // NOTE: All previous updates will be forgotten using this method.
    // ->latest()

    // (Optional). Limit to 2 updates (By default, updates starting with the earliest unconfirmed update are returned).
    ->limit(2)

    // (Optional). Add more params to the request.
    ->options([
        'timeout' => 0,
    ])
    ->get();

if($updates['ok']) {
    // Chat ID
    $chatId = $updates['result'][0]['message']['chat']['id'];
}
```

> [!NOTE]
> This method will not work if an outgoing webhook is set up.

For a complete list of available parameters for the `options`, see [Telegram Bot API docs][link-telegram-docs-getupdates].

## Using in Lumen

If you're using this notification channel in your Lumen project, you will have to add the below code in your `bootstrap/app.php` file.

```php
# bootstrap/app.php

// Make sure to create a "config/services.php" file and add the config from the above step.
$app->configure('services');

# Register the notification service providers.
$app->register(Illuminate\Notifications\NotificationServiceProvider::class);
$app->register(NotificationChannels\Telegram\TelegramServiceProvider::class);
```

## Proxy or Bridge Support

You may not be able to send notifications if Telegram Bot API is not accessible in your country,
you can either set a proxy by following the instructions [here](http://docs.guzzlephp.org/en/stable/quickstart.html#environment-variables) or
use a web bridge by setting the `base_uri` config above with the bridge uri.

You can set `HTTPS_PROXY` in your `.env` file.

## Usage

You can now use the channel in your `via()` method inside the Notification class.

### Text Notification

```php
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification
{
    public function via($notifiable)
    {
        return ["telegram"];
    }

    public function toTelegram($notifiable)
    {
        $url = url('/invoice/' . $notifiable->invoice->id);

        return TelegramMessage::create()
            // Optional recipient user id.
            ->to($notifiable->telegram_user_id)

            // Markdown supported.
            ->content("Hello there!")
            ->line("Your invoice has been *PAID*")
            ->lineIf($notifiable->amount > 0, "Amount paid: {$notifiable->amount}")
            ->line("Thank you!")

            // (Optional) Blade template for the content.
            // ->view('notification', ['url' => $url])

            // (Optional) Inline Buttons
            ->button('View Invoice', $url)
            ->button('Download Invoice', $url);

            // (Optional) Conditional notification.
            // Only send if amount is greater than 0. Otherwise, don't send.
            // ->sendWhen($notifiable->amount > 0)

            // (Optional) Inline Button with Web App
            // ->buttonWithWebApp('Open Web App', $url)

            // (Optional) Inline Button with callback. You can handle callback in your bot instance
            // ->buttonWithCallback('Confirm', 'confirm_invoice ' . $this->invoice->id)
    }
}
```

Here's a screenshot preview of the above notification on Telegram Messenger:

![Laravel Telegram Notification Example](https://user-images.githubusercontent.com/1915268/66616627-39be6180-ebef-11e9-92cc-f2da81da047a.jpg)

### Send with Keyboard

```php
public function toTelegram($notifiable)
{
    return TelegramPoll::create()
        ->to($notifiable)
        ->content('Choose an option:')
        ->keyboard('Button 1')
        ->keyboard('Button 2');
        // ->keyboard('Send your number', requestContact: true)
        // ->keyboard('Send your location', requestLocation: true);
}
```

Preview:

![Laravel Telegram Notification Keyboard](https://github.com/abbasudo/telegram/assets/86796762/9c10c7d0-740b-4270-bc7c-f0600e57ba7b)

### Send a Poll

```php
public function toTelegram($notifiable)
{
    return TelegramPoll::create()
        ->question('Which is your favorite Laravel Notification Channel?')
        ->choices(['Telegram', 'Facebook', 'Slack']);
}
```

Preview:

![Laravel Telegram Poll Example](https://github.com/user-attachments/assets/7324ccc5-9370-414a-9337-10c4e7446f5c)

### Attach a Contact

```php
public function toTelegram($notifiable)
{
    return TelegramContact::create()
            ->to($notifiable->telegram_user_id) // Optional
            ->firstName('John')
            ->lastName('Doe') // Optional
            ->phoneNumber('00000000');
}
```

Preview:

![Laravel Telegram Contact Example](https://github.com/user-attachments/assets/24f6e1c9-3ed6-4839-b9da-64ce09d09663)

### Attach an Audio

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
            ->to($notifiable->telegram_user_id) // Optional
            ->content('Audio') // Optional Caption
            ->audio('/path/to/audio.mp3');
}
```

Preview:

![Laravel Telegram Audio Notification Example](https://user-images.githubusercontent.com/60013703/143334174-4d796910-185f-46e2-89ad-5ec7a1a438c9.png)

### Attach a Photo

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->to($notifiable->telegram_user_id) // Optional
        ->content('Awesome *bold* text and [inline URL](http://www.example.com/)')
        ->file('/storage/archive/6029014.jpg', 'photo'); // local photo

        // OR using a helper method with or without a remote file.
        // ->photo('https://file-examples-com.github.io/uploads/2017/10/file_example_JPG_1MB.jpg');
}
```

Preview:

![Laravel Telegram Photo Notification Example](https://user-images.githubusercontent.com/1915268/66616792-daad1c80-ebef-11e9-9bdf-c0bc484cf037.jpg)

### Attach a Document

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->to($notifiable->telegram_user_id) // Optional
        ->content('Did you know we can set a custom filename too?')
        ->document('https://file-examples-com.github.io/uploads/2017/10/file-sample_150kB.pdf', 'sample.pdf');

        // You may also send document content on-fly.
        // ->document('Hello Text Document Content', 'hello.txt');
}
```

Preview:

![Laravel Telegram Document Notification Example](https://user-images.githubusercontent.com/1915268/66616850-10520580-ebf0-11e9-9122-4f4d263f3b53.jpg)

### Attach a Location

```php
public function toTelegram($notifiable)
{
    return TelegramLocation::create()
        ->latitude('40.6892494')
        ->longitude('-74.0466891');
}
```

Preview:

![Laravel Telegram Location Notification Example](https://user-images.githubusercontent.com/1915268/66616918-54450a80-ebf0-11e9-86ea-d5264fe05ba9.jpg)

### Attach a Venue

```php
public function toTelegram($notifiable)
{
    return TelegramVenue::create()
        ->latitude('38.8951')
        ->longitude('-77.0364')
        ->title('Grand Palace')
        ->address('Bangkok, Thailand');
}
```

Preview:

![Laravel Telegram Venue Notification Example](https://github.com/user-attachments/assets/96e762a6-c4b5-4d8d-8c2d-9d32adb754d0)

### Attach a Video

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->content('Sample *video* notification!')
        ->video('https://file-examples-com.github.io/uploads/2017/04/file_example_MP4_480_1_5MG.mp4');
}
```

Preview:

![Laravel Telegram Video Notification Example](https://user-images.githubusercontent.com/1915268/66617038-ed742100-ebf0-11e9-865a-bf0245d2cbbb.jpg)

### Attach a GIF File

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

### Attach a Sticker

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->sticker(storage_path('telegram/AnimatedSticker.tgs'));
}
```

Preview:

![Laravel Telegram Sticker Notification Example](https://github.com/user-attachments/assets/5206aac7-022c-4288-ae26-3a117f117fe0)

### Routing a Message

You can either send the notification by providing with the chat ID of the recipient to the `to($chatId)` method like shown in the previous examples or add a `routeNotificationForTelegram()` method in your notifiable model:

```php
/**
 * Route notifications for the Telegram channel.
 *
 * @return int
 */
public function routeNotificationForTelegram()
{
    return $this->telegram_user_id;
}
```

### Handling Response

You can make use of the [notification events](https://laravel.com/docs/11.x/notifications#notification-events) to handle the response from Telegram. On success, your event listener will receive a [Message](https://core.telegram.org/bots/api#message) object with various fields as appropriate to the notification type.

For a complete list of response fields, please refer the Telegram Bot API's [Message object](https://core.telegram.org/bots/api#message) docs.

### Exception Handling

In case of failures, the package provides two ways to handle exceptions.

#### Using NotificationFailed Event

> You can listen to the `Illuminate\Notifications\Events\NotificationFailed` event, which provides a `$data` array containing `to`, `request`, and `exception` keys.

Listener example:
```php
use Illuminate\Notifications\Events\NotificationFailed;

class HandleNotificationFailure
{
    public function handle(NotificationFailed $event)
    {
        // $event->notification: The notification instance.
        // $event->notifiable: The notifiable entity who received the notification.
        // $event->channel: The channel name.
        // $event->data: The data needed to process this failure.

        if ($event->channel !== 'telegram') {
            return;
        }

        // Log the error / notify administrator or disable notification channel for the user, etc.
        \Log::error('Telegram notification failed', [
            'chat_id' => $event->data['to'],
            'error' => $event->data['exception']->getMessage(),
            'request' => $event->data['request']
        ]);
    }
}
```

#### Using onError Callback

> You can handle exceptions for individual notifications using the `onError` method in your notification:

```php
public function toTelegram($notifiable)
{
    return TelegramMessage::create()
        ->content('Hello!')
        ->onError(function ($data) {
            \Log::error('Failed to send Telegram notification', [
                'chat_id' => $data['to'],
                'error' => $data['exception']->getMessage()
            ]);
        });
}
```

In both methods, the `$data` array contains the following keys:

- `to`: The recipient's chat ID.
- `request`: The payload sent to the Telegram Bot API.
- `exception`: The exception object containing error details.

### On-Demand Notifications

> Sometimes you may need to send a notification to someone who is not stored as a "user" of your application. Using the `Notification::route` method, you may specify ad-hoc notification routing information before sending the notification. For more details, you can check out the [on-demand notifications][link-on-demand-notifications] docs.

```php
use Illuminate\Support\Facades\Notification;

Notification::route('telegram', 'TELEGRAM_CHAT_ID')
            ->notify(new InvoicePaid($invoice));
```

### Sending to Multiple Recipients

Using the [notification facade][link-notification-facade] you can send a notification to multiple recipients at once.

> [!WARNING]
> If you're sending bulk notifications to multiple users, the Telegram Bot API will not allow more than 30 messages per second or so.
> Consider spreading out notifications over large intervals of 8—12 hours for best results.
>
> Also note that your bot will not be able to send more than 20 messages per minute to the same group.
>
> If you go over the limit, you'll start getting `429` errors. For more details, refer Telegram Bots [FAQ](https://core.telegram.org/bots/faq#broadcasting-to-users).

```php
use Illuminate\Support\Facades\Notification;

// Recipients can be an array of chat IDs or collection of notifiable entities.
Notification::send($recipients, new InvoicePaid());
```

## Available Methods

For more information on supported parameters, check out these [docs](https://core.telegram.org/bots/api#sendmessage).

### Common Methods

> These methods are optional and common across all the API methods.

- `to(int|string $chatId)` - Set recipient's chat ID.
- `token(string $token)` - Override default bot token.
- `parseMode(enum ParseMode $mode)` - Set message parse mode (or `normal()` to unset). Default is `ParseMode::Markdown`.
- `keyboard(string $text, int $columns = 2, bool $requestContact = false, bool $requestLocation = false)` - Add regular keyboard. You can add as many as you want, and they'll be placed 2 in a row by default.
- `button(string $text, string $url, int $columns = 2)` - Add inline CTA button.
- `buttonWithCallback(string $text, string $callbackData, int $columns = 2)` - Add inline button with callback.
- `buttonWithWebApp(string $text, string $url, int $columns = 2)` - Add inline web app button.
- `disableNotification(bool $disableNotification = true)` - Send silently (notification without sound).
- `options(array $options)` - Add/override payload parameters.
- `sendWhen(bool $condition)` - Set condition for sending. If the condition is true, the notification will be sent; otherwise, it will not.
- `onError(Closure $callback)` - Set error handler (receives a data array with `to`, `request`, `exception` keys).
- `getPayloadValue(string $key)` - Get specific payload value.

### Telegram Message Methods

> Telegram message notifications are used to send text messages to the user. Supports [Telegram formatting options](https://core.telegram.org/bots/api#formatting-options)

- `content(string $content, int $limit = null)` - Set message content with optional length limit. Supports markdown.
- `line(string $content)` - Add new line of content.
- `lineIf(bool $condition, string $content)` - Conditionally add new line.
- `escapedLine(string $content)` - Add escaped content line (for Markdown).
- `view(string $view, array $data = [], array $mergeData = [])` - Use Blade template with Telegram supported HTML or Markdown syntax content if you wish to use a view file instead of the `content()` method.
- `chunk(int $limit = 4096)` - Split long messages (rate limited to 1/second).

> [!NOTE]
> Chunked messages will be rate limited to one message per second to comply with rate limitation requirements from Telegram.

### Telegram Location Methods

> Telegram location messages are used to share a geographical location with the user.

- `latitude(float|string $latitude)` - Set location latitude.
- `longitude(float|string $longitude)` - Set location longitude.

### Telegram Venue Methods

> Telegram venue messages are used to share a geographical location information about a venue.

- `latitude(float|string $latitude)` - Set venue latitude.
- `longitude(float|string $longitude)` - Set venue longitude.
- `title(string $title)` - Set venue name/title.
- `address(string $address)` - Set venue address.
- `foursquareId(string $foursquareId)` - (Optional) Set Foursquare identifier of the venue.
- `foursquareType(string $foursquareType)` - (Optional) Set Foursquare type of the venue, if known.
- `googlePlaceId(string $googlePlaceId)` - (Optional) Set Google Places identifier of the venue.
- `googlePlaceType(string $googlePlaceType)` - (Optional) Set Google Places type of the venue.

### Telegram File Methods

> Telegram file messages are used to share various types of files with the user.

- `content(string $content)` - Set file caption. Supports markdown.
- `view(string $view, array $data = [], array $mergeData = [])` - Use Blade template for caption.
- `file(string|resource|StreamInterface $file, FileType|string $type, string $filename = null)` - Attach file by path/URL. Types: `photo`, `audio`, `document`, `video`, `animation`, `voice`, `video_note`, `sticker` (Use Enum `Enums\FileType`). Use helper methods below for convenience. Filename is optional, ex: `sample.pdf`.

#### Helper Methods:

- `photo(string $file)` - Send photo.
- `audio(string $file)` - Send audio (MP3).
- `document(string $file, string $filename = null)` - Send document or any file as document.
- `video(string $file)` - Send video.
- `animation(string $file)` - Send animated GIF.
- `voice(string $file)` - Send voice note (OGG/OPUS).
- `videoNote(string $file)` - Send video note (≤1min, rounded square video).
- `sticker(string $file)` - Send sticker (static PNG/WEBP, animated .TGS, or video .WEBM stickers).

### Telegram Contact Methods

> Telegram contact messages are used to share contact information with the user.

- `phoneNumber(string $phone)` - Set contact phone.
- `firstName(string $name)` - Set contact first name.
- `lastName(string $name)` - Set contact last name (optional).
- `vCard(string $vcard)` - Set contact vCard (optional).

### Telegram Poll Methods

> Telegram polls are a type of interactive message that allows users to vote on a question. Polls can be used to gather feedback, make decisions, or even run contests.

- `question(string $question)` - Set poll question.
- `choices(array $choices)` - Set poll choices.

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

- [Irfaq Syed][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-phpchat]: https://img.shields.io/badge/Slack-PHP%20Chat-5c6aaa.svg?style=flat-square&logo=slack&labelColor=4A154B
[ico-telegram]: https://img.shields.io/badge/@PHPChatCo-2CA5E0.svg?style=flat-square&logo=telegram&label=Telegram
[ico-version]: https://img.shields.io/packagist/v/laravel-notification-channels/telegram.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/laravel-notification-channels/telegram.svg?style=flat-square

[link-phpchat]: https://phpchat.co/?ref=laravel-channel-telegram
[link-telegram]: https://t.me/PHPChatCo
[link-repo]: https://github.com/laravel-notification-channels/telegram
[link-packagist]: https://packagist.org/packages/laravel-notification-channels/telegram
[link-author]: https://github.com/irazasyed
[link-contributors]: ../../contributors
[link-notification-facade]: https://laravel.com/docs/11.x/notifications#using-the-notification-facade
[link-on-demand-notifications]: https://laravel.com/docs/11.x/notifications#on-demand-notifications
[link-telegram-docs-update]: https://core.telegram.org/bots/api#update
[link-telegram-docs-getupdates]: https://core.telegram.org/bots/api#getupdates
