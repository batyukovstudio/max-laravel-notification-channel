<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use NotificationChannels\Max\MaxMessage;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;
use NotificationChannels\Max\Tests\TestSupport\TestNotifiable;
use NotificationChannels\Max\Tests\TestSupport\TestNotification;
use NotificationChannels\Max\Tests\TestSupport\TestNotificationNoRecipient;
use NotificationChannels\Max\Tests\TestSupport\TestStringNotification;

it('can send a MAX message', function () {
    $notifiable = new TestNotifiable;
    $notification = new TestNotification;

    $expectedResponse = ['message' => ['body' => ['mid' => 'message-1']]];
    $actualResponse = $this->sendMockNotification($notifiable, $notification, $expectedResponse);

    expect($actualResponse)->toBe($expectedResponse);
});

it('dispatches NotificationFailed when MAX delivery fails', function () {
    $notifiable = new TestNotifiable(maxUserId: 12345);
    $notification = new TestNotification;
    $exception = CouldNotSendNotification::couldNotCommunicateWithMax('connection refused');

    $this->client
        ->shouldReceive('sendMessage')
        ->once()
        ->andThrow($exception);

    $this->dispatcher
        ->expects($this->once())
        ->method('dispatch')
        ->with($this->callback(function (NotificationFailed $event) use ($notifiable, $notification, $exception): bool {
            return $event->channel === 'max'
                && $event->notifiable === $notifiable
                && $event->notification === $notification
                && $event->data['to'] === ['user_id' => 12345]
                && $event->data['exception']->getMessage() === $exception->getMessage();
        }));

    $this->channel->send($notifiable, $notification);
})->throws(CouldNotSendNotification::class);

it('returns null when notification does not define toMax', function () {
    $result = $this->channel->send(new TestNotifiable, new class extends Notification {});

    expect($result)->toBeNull();
});

it('returns null when message sending is disabled', function () {
    $notification = new class extends Notification
    {
        public function toMax($notifiable): MaxMessage
        {
            return MaxMessage::create('No-op')->sendWhen(false);
        }
    };

    expect($this->channel->send(new TestNotifiable, $notification))->toBeNull();
});

it('supports string-based notifications and resolves routed recipients', function () {
    $notifiable = new TestNotifiable(maxUserId: 67890);

    $this->client
        ->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function (MaxMessage $message): bool {
            return $message->toQuery() === ['user_id' => 67890]
                && $message->toBody() === ['text' => 'String-based MAX notification'];
        })
        ->andReturn(new Response(200, [], json_encode([
            'message' => ['body' => ['mid' => 'message-2']],
        ])));

    $result = $this->channel->send($notifiable, new TestStringNotification);

    expect($result)->toBe([
        'message' => ['body' => ['mid' => 'message-2']],
    ]);
});

it('resolves chat recipients from routeNotificationForMax', function () {
    $notifiable = new TestNotifiable(maxUserId: null, maxChatId: -100500);

    $this->client
        ->shouldReceive('sendMessage')
        ->once()
        ->withArgs(function (MaxMessage $message): bool {
            return $message->toQuery() === ['chat_id' => -100500]
                && $message->toBody() === ['text' => 'MAX notifications are production-ready.'];
        })
        ->andReturn(new Response(200, [], json_encode([
            'message' => ['body' => ['mid' => 'message-3']],
        ])));

    $result = $this->channel->send($notifiable, new TestNotificationNoRecipient);

    expect($result)->toBe([
        'message' => ['body' => ['mid' => 'message-3']],
    ]);
});

it('throws when no MAX recipient can be resolved', function () {
    $this->channel->send(new TestNotifiable(maxUserId: null), new TestNotificationNoRecipient);
})->throws(CouldNotSendNotification::class, 'No MAX recipient was provided.');
