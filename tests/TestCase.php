<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Notification;
use Mockery;
use NotificationChannels\Max\MaxChannel;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;
use NotificationChannels\Max\MaxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected MaxClient $client;

    protected Dispatcher $dispatcher;

    protected MaxChannel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addLocation(__DIR__.'/TestSupport');

        $this->app->singleton(MaxClient::class, function () {
            return Mockery::mock(MaxClient::class);
        });

        $this->client = app(MaxClient::class);
        $this->dispatcher = $this->createMock(Dispatcher::class);
        $this->channel = new MaxChannel($this->dispatcher);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function sendMockNotification(
        mixed $notifiable,
        Notification $notification,
        array $expectedResponse
    ): ?array {
        $this->client
            ->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (MaxMessage $message) use ($notification, $notifiable): bool {
                return $message->toArray() === $notification->toMax($notifiable)->toArray();
            })
            ->andReturn(new Response(200, [], json_encode($expectedResponse)));

        return $this->channel->send($notifiable, $notification);
    }

    protected function getPackageProviders($app): array
    {
        return [
            MaxServiceProvider::class,
        ];
    }
}
