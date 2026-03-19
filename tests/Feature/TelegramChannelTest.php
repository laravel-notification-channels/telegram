<?php

namespace NotificationChannels\Telegram\Tests\Feature;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Events\NotificationFailed;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotificationNoChatId;

it('can send a message', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestNotification;

    $expectedResponse = ['ok' => true, 'result' => ['message_id' => 123, 'chat' => ['id' => 12345]]];
    $actualResponse = $this->sendMockNotification('sendMessage', $notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});

test('notification failed event', function () {
    self::expectException($exception_class = CouldNotSendNotification::class);
    self::expectExceptionMessage($exception_message = 'Some exception');

    $notifiable = new TestNotifiable;
    $notification = new TestNotification;

    $payload = $notification->toTelegram($notifiable)->toArray();

    $this->telegram
        ->shouldReceive('sendMessage')
        ->andThrow($exception_class, $exception_message);

    $this->dispatcher
        ->expects($this->once())
        ->method('dispatch')
        ->with(
            new NotificationFailed(
                $notifiable,
                $notification,
                'telegram',
                [
                    'to' => $payload['chat_id'],
                    'request' => $payload,
                    'exception' => new $exception_class($exception_message),
                ]
            )
        );

    $this->channel->send($notifiable, $notification);
});

it('returns null when notification does not define toTelegram', function () {
    $result = $this->channel->send(new TestNotifiable, new class extends Notification {});

    expect($result)->toBeNull();
});

it('returns null when message sending is disabled', function () {
    $notification = new class extends Notification
    {
        public function toTelegram($notifiable): TelegramMessage
        {
            return TelegramMessage::create('No-op')->sendWhen(false);
        }
    };

    $result = $this->channel->send(new TestNotifiable, $notification);

    expect($result)->toBeNull();
});

it('uses the routed telegram recipient when chat id is not set on the message', function () {
    $notifiable = new class
    {
        public function routeNotificationFor(string $driver, Notification $notification): int|false
        {
            return $driver === 'telegram' ? 67890 : false;
        }
    };

    $notification = new TestNotificationNoChatId;
    $expectedResponse = ['ok' => true, 'result' => ['message_id' => 123, 'chat' => ['id' => 67890]]];

    $this->telegram
        ->shouldReceive('sendMessage')
        ->with([
            'text' => '',
            'parse_mode' => 'Markdown',
            'chat_id' => 67890,
        ])
        ->once()
        ->andReturn(new \GuzzleHttp\Psr7\Response(200, [], json_encode($expectedResponse)));

    expect($this->channel->send($notifiable, $notification))->toBe($expectedResponse);
});

it('returns null when no telegram recipient can be resolved', function () {
    $result = $this->channel->send(new TestNotifiable, new TestNotificationNoChatId);

    expect($result)->toBeNull();
});
