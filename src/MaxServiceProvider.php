<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

final class MaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MaxClient::class, function (Application $app): MaxClient {
            /** @var string|null $token */
            $token = config('services.max.token');
            /** @var string|null $baseUri */
            $baseUri = config('services.max.base_uri');

            return new MaxClient(
                $token,
                $app->make(HttpClient::class),
                $baseUri
            );
        });

        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('max', fn (Application $app) => $app->make(MaxChannel::class));
        });
    }
}
