<?php

use Illuminate\Notifications\Events\NotificationFailed;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotification;

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
