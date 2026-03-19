# Telegram Notifications Channel for Laravel

[![Join PHP Chat][ico-phpchat]][link-phpchat]
[![Chat on Telegram][ico-telegram]][link-telegram]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-packagist]

This package makes it easy to send Telegram notifications from Laravel via the [Telegram Bot API](https://core.telegram.org/bots).

## Contents

- [Installation](#installation)
  - [Setting up your Telegram Bot](#setting-up-your-telegram-bot)
  - [Retrieving Chat ID](#retrieving-chat-id)
  - [Using in Lumen](#using-in-lumen)
  - [Proxy or Bridge Support](#proxy-or-bridge-support)
- [Usage](#usage)
  - [Text Notification](#text-notification)
  - [Send with Keyboard](#send-with-keyboard)
  - [Send a Dice](#send-a-dice)
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
  - [Send a Media Group](#send-a-media-group)
  - [Routing a Message](#routing-a-message)
  - [Handling Response](#handling-response)
  - [Exception Handling](#exception-handling)
    - [Using NotificationFailed Event](#using-notificationfailed-event)
    - [Using onError Callback](#using-onerror-callback)
  - [On-Demand Notifications](#on-demand-notifications)
  - [Sending to Multiple Recipients](#sending-to-multiple-recipients)
  - [Using the Telegram Client Directly](#using-the-telegram-client-directly)
- [Available Methods](#available-methods)
  - [Common Methods](#common-methods)
  - [Telegram Message Methods](#telegram-message-methods)
  - [Telegram Location Methods](#telegram-location-methods)
  - [Telegram Venue Methods](#telegram-venue-methods)
  - [Telegram File Methods](#telegram-file-methods)
  - [Telegram Media Group Methods](#telegram-media-group-methods)
  - [Telegram Contact Methods](#telegram-contact-methods)
  - [Telegram Dice Methods](#telegram-dice-methods)
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

Talk to [@BotFather](https://core.telegram.org/bots/features#creating-a-new-bot) and generate a Bot API token.

Then, configure your Telegram Bot API token:

```php
# config/services.php

'telegram' => [
    'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR BOT TOKEN HERE'),
    // Optional bridge / self-hosted Bot API server
    // 'base_uri' => env('TELEGRAM_API_BASE_URI'),
],
```

> [!NOTE]
> The package also supports the legacy `services.telegram-bot-api.*` config keys for backward compatibility, but `services.telegram.*` is the preferred configuration format.

## Retrieving Chat ID

To send notifications to a Telegram user, channel, or group, you need its chat ID.

You can retrieve it by fetching your bot [updates][link-telegram-docs-update] with the `getUpdates` method described in the Telegram Bot API [docs][link-telegram-docs-getupdates].

An [update][link-telegram-docs-update] is an object whose shape depends on the event type, such as `message`, `callback_query`, or `poll`. For the full list of fields, see the [Telegram Bot API docs][link-telegram-docs-update].

To make this easier, the package ships with `TelegramUpdates`, which lets you fetch updates and inspect the chat IDs you need.

Keep in mind that the user must interact with your bot first before you can obtain their chat ID and store it for future notifications.

Here's an example of fetching an update:

```php
use NotificationChannels\Telegram\TelegramUpdates;

// Response is an array of updates.
$updates = TelegramUpdates::create()

    // (Optional) Get the latest update.
    // NOTE: All previous updates will be forgotten using this method.
    // ->latest()

    // (Optional) Limit to 2 updates. By default, updates start with the earliest unconfirmed update.
    ->limit(2)

    // (Optional) Add more request parameters.
    ->options([
        'timeout' => 0,
    ])
    ->get();

if ($updates['ok']) {
    // Chat ID
    $chatId = $updates['result'][0]['message']['chat']['id'];
}
```

> [!NOTE]
> This method will not work while an outgoing webhook is configured.

For the full list of supported `options()`, see the [Telegram Bot API docs][link-telegram-docs-getupdates].

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

You may not be able to send notifications directly if the Telegram Bot API is blocked in your region.
In that case, you can either configure a proxy by following the Guzzle instructions [here](http://docs.guzzlephp.org/en/stable/quickstart.html#environment-variables) or
point the package at a bridge or self-hosted Bot API server by setting the `base_uri` config shown above.

You can also set `HTTPS_PROXY` in your `.env` file.

## Usage

You can now return the channel from your notification's `via()` method.

### Text Notification

```php
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification
{
    public function via($notifiable)
    {
        return ['telegram'];
    }

    public function toTelegram($notifiable)
    {
        $url = url('/invoice/' . $notifiable->invoice->id);
        $user = $notifiable->name;

        return TelegramMessage::create()
            ->to($notifiable->telegram_user_id)
            ->content('Hello there!')
            ->line('Your invoice has been *PAID*')
            ->lineIf($notifiable->amount > 0, "Amount paid: {$notifiable->amount}")
            ->line('Thank you, '.TelegramMessage::escapeMarkdown($user).'!')
            // ->view('notification', ['url' => $url])
            ->button('View Invoice', $url)
            ->button('Download Invoice', $url);

        // Other fluent helpers are also available:
        // ->businessConnectionId('business-connection-id')
        // ->messageThreadId(42)
        // ->protectContent()
        // ->directMessagesTopicId(1001)
        // ->allowPaidBroadcast()
        // ->messageEffectId('5104841245755180586')
        // ->replyParameters(['message_id' => 123])
        // ->suggestedPostParameters(['price' => ['amount' => 10, 'currency' => 'XTR']])
        // ->entities([...])
        // ->linkPreviewOptions(['is_disabled' => true])
        // ->sendWhen($notifiable->amount > 0)
        // ->buttonWithWebApp('Open Web App', $url)
        // ->buttonWithCallback('Confirm', 'confirm_invoice '.$this->invoice->id)
    }
}
```

Here's a screenshot preview of the above notification on Telegram Messenger:

![Laravel Telegram Notification Example](https://user-images.githubusercontent.com/1915268/66616627-39be6180-ebef-11e9-92cc-f2da81da047a.jpg)

### Send with Keyboard

```php
public function toTelegram($notifiable)
{
    return TelegramMessage::create()
        ->to($notifiable->telegram_user_id)
        ->content('Choose an option:')
        ->keyboard('Button 1')
        ->keyboard('Button 2');
}
```

You can also request structured input from the keyboard:

```php
TelegramMessage::create()
    ->keyboard('Send your number', requestContact: true)
    ->keyboard('Send your location', requestLocation: true);
```

Preview:

![Laravel Telegram Notification Keyboard](https://github.com/abbasudo/telegram/assets/86796762/9c10c7d0-740b-4270-bc7c-f0600e57ba7b)

### Send a Dice

```php
use NotificationChannels\Telegram\TelegramDice;

public function toTelegram($notifiable)
{
    return TelegramDice::create()
        ->to($notifiable->telegram_user_id)
        ->emoji('🎯');
}
```

### Send a Poll

```php
public function toTelegram($notifiable)
{
    return TelegramPoll::create()
        ->to($notifiable->telegram_user_id)
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
        ->content('Audio') // Optional caption
        ->captionEntities([
            ['offset' => 0, 'length' => 5, 'type' => 'bold'],
        ])
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
        ->showCaptionAboveMedia()
        ->file('/storage/archive/6029014.jpg', 'photo'); // local photo
}
```

You can also use a helper method with a remote file or Telegram file ID:

```php
TelegramFile::create()
    ->photo('https://file-examples-com.github.io/uploads/2017/10/file_example_JPG_1MB.jpg');
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
}
```

Raw file contents are also supported when you provide a filename:

```php
TelegramFile::create()
    ->document('Hello Text Document Content', 'hello.txt');
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

### Attach a Venue

```php
public function toTelegram($notifiable)
{
    return TelegramVenue::create()
        ->to($notifiable->telegram_user_id)
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
        ->to($notifiable->telegram_user_id)
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
        ->to($notifiable->telegram_user_id)
        ->content('Woot! We can send animated gif notifications too!')
        ->animation('https://sample-videos.com/gif/2.gif');
}
```

Local files work the same way:

```php
TelegramFile::create()
    ->animation('/path/to/some/animated.gif');
```

Preview:

![Laravel Telegram Gif Notification Example](https://user-images.githubusercontent.com/1915268/66617071-109ed080-ebf1-11e9-989b-b237f2b9502d.jpg)

### Attach a Sticker

```php
public function toTelegram($notifiable)
{
    return TelegramFile::create()
        ->to($notifiable->telegram_user_id)
        ->sticker(storage_path('telegram/AnimatedSticker.tgs'));
}
```

Preview:

![Laravel Telegram Sticker Notification Example](https://github.com/user-attachments/assets/5206aac7-022c-4288-ae26-3a117f117fe0)

### Send a Media Group

```php
use NotificationChannels\Telegram\TelegramMediaGroup;

public function toTelegram($notifiable)
{
    return TelegramMediaGroup::create()
        ->to($notifiable->telegram_user_id)
        ->messageThreadId(42)
        ->photo('https://example.com/one.jpg', 'First image')
        ->photo('https://example.com/two.jpg')
        ->document('Quarterly report content', 'Quarterly report', 'report.txt');
}
```

Uploaded files are also supported:

```php
TelegramMediaGroup::create()
    ->photo(storage_path('app/telegram/one.jpg'), 'First image')
    ->video(storage_path('app/telegram/video.mp4'), 'Release demo');
```

Media groups support albums of `photo`, `video`, `audio`, and `document` items. Each item may be a Telegram file ID, a remote URL, a local path, a stream/resource, or raw file contents when you provide a filename. Uploaded files are sent automatically as multipart attachments.

### Routing a Message

You can either send a notification by setting the recipient explicitly with `to($chatId)` as shown above, or define `routeNotificationForTelegram()` on your notifiable model:

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

You can use [notification events](https://laravel.com/docs/13.x/notifications#notification-events) to handle Telegram responses. On success, your listener receives a [Message](https://core.telegram.org/bots/api#message) object with fields appropriate to the notification type.

For the full list of response fields, refer to the Telegram Bot API [Message object](https://core.telegram.org/bots/api#message) docs.

### Exception Handling

For failures, the package provides two exception-handling hooks.

#### Using NotificationFailed Event

> You can listen to `Illuminate\Notifications\Events\NotificationFailed`, which provides a `$data` array containing `to`, `request`, and `exception` keys.

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

> You can handle exceptions for an individual notification by attaching an `onError` callback:

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

> Sometimes you may want to send a Telegram notification to someone who is not stored as a notifiable model. With `Notification::route`, you can provide ad-hoc routing information before dispatching the notification. For more details, see the [on-demand notifications][link-on-demand-notifications] docs.

```php
use Illuminate\Support\Facades\Notification;

Notification::route('telegram', 'TELEGRAM_CHAT_ID')
            ->notify(new InvoicePaid($invoice));
```

### Sending to Multiple Recipients

Using the [notification facade][link-notification-facade], you can send a notification to multiple recipients at once.

> [!WARNING]
> If you're sending bulk notifications to many users, the Telegram Bot API will not allow much more than 30 messages per second.
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

### Using the Telegram Client Directly

If you need lower-level Bot API access, you can resolve the `Telegram` client directly from the container:

```php
use NotificationChannels\Telegram\Telegram;

$telegram = app(Telegram::class);

$telegram->sendChatAction([
    'chat_id' => $chatId,
    'action' => 'typing',
]);

$telegram->editMessageText([
    'chat_id' => $chatId,
    'message_id' => $messageId,
    'text' => 'Updated message text',
]);

$telegram->deleteMessage([
    'chat_id' => $chatId,
    'message_id' => $messageId,
]);

$telegram->sendMediaGroup([
    'chat_id' => $chatId,
    'media' => json_encode([
        ['type' => 'photo', 'media' => 'https://example.com/one.jpg', 'caption' => 'First'],
        ['type' => 'photo', 'media' => 'https://example.com/two.jpg'],
    ], JSON_THROW_ON_ERROR),
]);

$telegram->stopPoll([
    'chat_id' => $chatId,
    'message_id' => $pollMessageId,
]);
```

Available direct client helpers currently include:

- `sendMessage(array $params)`
- `sendFile(array $params, string $type, bool $multipart = false)`
- `sendMediaGroup(array $params, bool $multipart = false)`
- `sendPoll(array $params)`
- `sendContact(array $params)`
- `sendLocation(array $params)`
- `sendVenue(array $params)`
- `sendDice(array $params)`
- `sendChatAction(array $params)`
- `editMessageText(array $params)`
- `editMessageCaption(array $params)`
- `editMessageMedia(array $params, bool $multipart = false)`
- `editMessageReplyMarkup(array $params)`
- `stopPoll(array $params)`
- `deleteMessage(array $params)`
- `deleteMessages(array $params)`
- `getUpdates(array $params)`

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
- `businessConnectionId(string $businessConnectionId)` - Send on behalf of a connected business account.
- `messageThreadId(int $messageThreadId)` - Send to a forum / topic thread.
- `directMessagesTopicId(int $directMessagesTopicId)` - Send to a channel direct message topic.
- `protectContent(bool $protect = true)` - Protect content from forwarding and saving.
- `allowPaidBroadcast(bool $allow = true)` - Allow paid high-throughput broadcasts.
- `messageEffectId(string $messageEffectId)` - Add a private-chat message effect.
- `replyParameters(array $replyParameters)` - Set structured reply parameters.
- `suggestedPostParameters(array $suggestedPostParameters)` - Set suggested post parameters for supported direct message topics.
- `options(array $options)` - Add/override payload parameters.
- `sendWhen(bool $condition)` - Set condition for sending. If the condition is true, the notification will be sent; otherwise, it will not.
- `onError(callable $callback)` - Set error handler (receives a data array with `to`, `request`, `exception` keys).
- `getPayloadValue(string $key)` - Get specific payload value.

### Telegram Message Methods

> Telegram message notifications are used to send text messages to the user. Supports [Telegram formatting options](https://core.telegram.org/bots/api#formatting-options)

- `content(string $content, int $limit = null)` - Set message content with optional length limit. Supports markdown.
- `line(string $content)` - Add new line of content.
- `lineIf(bool $condition, string $content)` - Conditionally add new line.
- `escapedLine(string $content)` - Add escaped content line (for Markdown).
- `view(string $view, array $data = [], array $mergeData = [])` - Use Blade template with Telegram supported HTML or Markdown syntax content if you wish to use a view file instead of the `content()` method.
- `entities(array $entities)` - Set explicit message entities instead of using `parse_mode`.
- `linkPreviewOptions(array $linkPreviewOptions)` - Set Telegram link preview options.
- `chunk(int $limit = 4096)` - Split long messages (rate limited to 1/second).

> [!NOTE]
> Chunked messages will be rate limited to one message per second to comply with rate limitation requirements from Telegram.

#### Helper Methods:

- `escapeMarkdown(string $content)` - Escape a string to make it safe for the `markdownv2` parse mode

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
- `captionEntities(array $captionEntities)` - Set explicit caption entities.
- `showCaptionAboveMedia(bool $show = true)` - Show caption above supported media types.
- `file(string|resource|StreamInterface $file, FileType|string $type, string $filename = null)` - Attach a local path, remote URL, Telegram file ID, stream/resource, or raw file contents. Types: `photo`, `audio`, `document`, `video`, `animation`, `voice`, `video_note`, `sticker` (use `Enums\FileType`). Pass a filename when the string represents raw file contents.

#### Helper Methods:

- `photo(string $file)` - Send photo.
- `audio(string $file)` - Send audio (MP3).
- `document(string $file, string $filename = null)` - Send document or any file as document.
- `video(string $file)` - Send video.
- `animation(string $file)` - Send animated GIF.
- `voice(string $file)` - Send voice note (OGG/OPUS).
- `videoNote(string $file)` - Send video note (≤1min, rounded square video).
- `sticker(string $file)` - Send sticker (static PNG/WEBP, animated .TGS, or video .WEBM stickers).

### Telegram Media Group Methods

> Telegram media groups are albums of `photo`, `video`, `audio`, or `document` items sent as a single notification.

- `photo(string|resource|StreamInterface $media, string $caption = null, string $filename = null)` - Add a photo to the group.
- `video(string|resource|StreamInterface $media, string $caption = null, string $filename = null)` - Add a video to the group.
- `audio(string|resource|StreamInterface $media, string $caption = null, string $filename = null)` - Add an audio file to the group.
- `document(string|resource|StreamInterface $media, string $caption = null, string $filename = null)` - Add a document to the group.
- `hasAttachments()` - Determine if the group contains uploaded files and requires multipart transport.

Each media item may be a Telegram file ID, a URL, a local path, a stream/resource, or raw file contents when paired with a filename.

### Telegram Contact Methods

> Telegram contact messages are used to share contact information with the user.

- `phoneNumber(string $phone)` - Set contact phone.
- `firstName(string $name)` - Set contact first name.
- `lastName(string $name)` - Set contact last name (optional).
- `vCard(string $vcard)` - Set contact vCard (optional).

### Telegram Dice Methods

> Telegram dice messages are interactive emoji dice / darts / slots / bowling style messages.

- `emoji(string $emoji)` - Set the dice emoji (`🎲`, `🎯`, `🎳`, `🏀`, `⚽`, `🎰`, etc.).

### Telegram Poll Methods

> Telegram polls are a type of interactive message that allows users to vote on a question. Polls can be used to gather feedback, make decisions, or even run contests.

- `question(string $question)` - Set poll question.
- `choices(array $choices)` - Set poll choices.

## Alternatives

For advanced usage, please consider using [telegram-bot-sdk](https://github.com/irazasyed/telegram-bot-sdk) instead.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for details about recent changes.

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
[link-notification-facade]: https://laravel.com/docs/13.x/notifications#using-the-notification-facade
[link-on-demand-notifications]: https://laravel.com/docs/13.x/notifications#on-demand-notifications
[link-telegram-docs-update]: https://core.telegram.org/bots/api#update
[link-telegram-docs-getupdates]: https://core.telegram.org/bots/api#getupdates
