<?php

use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Events\NotificationFailed;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Telegram\Tests\TestSupport\TestNotification;

it('can send a message', function () {
    $expectedResponse = ['ok' => true, 'result' => ['message_id' => 123, 'chat' => ['id' => 12345]]];

    $this->telegram
        ->shouldReceive('sendMessage')
        ->once()
        ->with([
            'text'       => 'Laravel Notification Channels are awesome!',
            'parse_mode' => 'Markdown',
            'chat_id'    => 12345,
        ])
        ->andReturns(new Response(200, [], json_encode($expectedResponse)));

    $actualResponse = $this->channel->send(new TestNotifiable(), new TestNotification());

    expect($actualResponse)->toBe($expectedResponse);
});

test('notification failed event', function () {
    self::expectException($exception_class = CouldNotSendNotification::class);
    self::expectExceptionMessage($exception_message = 'Some exception');

    $notifiable = new TestNotifiable();
    $notification = new TestNotification();

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
                []
            )
        );

    $this->channel->send($notifiable, $notification);
});
