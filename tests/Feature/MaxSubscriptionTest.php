<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Mockery;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxSubscription;

it('manages webhook subscriptions for MAX', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'GET' && $uri === 'https://platform-api.max.ru/subscriptions')->andReturn(new Response(200, [], json_encode(['subscriptions' => []])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'POST' && $uri === 'https://platform-api.max.ru/subscriptions' && $options['json'] === ['url' => 'https://example.com/webhook', 'update_types' => ['message_created'], 'secret' => 'secret_123'])->andReturn(new Response(200, [], json_encode(['success' => true])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'DELETE' && $uri === 'https://platform-api.max.ru/subscriptions' && $options['query'] === ['url' => 'https://example.com/webhook'])->andReturn(new Response(200, [], json_encode(['success' => true])));

    $subscription = new MaxSubscription(new MaxClient('token', $http));
    $subscription
        ->url('https://example.com/webhook')
        ->updateTypes(['message_created'])
        ->secret('secret_123');

    expect($subscription->all())->toBe(['subscriptions' => []])
        ->and($subscription->subscribe())->toBe(['success' => true])
        ->and($subscription->unsubscribe())->toBe(['success' => true]);
});
