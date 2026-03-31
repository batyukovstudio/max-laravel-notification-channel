<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Mockery;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxUpdates;

it('fetches MAX updates with long polling options', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options): bool {
            return $method === 'GET'
                && $uri === 'https://platform-api.max.ru/updates'
                && $options['query'] === [
                    'limit' => 2,
                    'timeout' => 0,
                    'marker' => 15,
                    'types' => 'message_created,message_callback',
                ];
        })
        ->andReturn(new Response(200, [], json_encode([
            'updates' => [['update_type' => 'message_created']],
            'marker' => 16,
        ])));

    $updates = new MaxUpdates(new MaxClient('token', $http));

    expect(
        $updates
            ->limit(2)
            ->timeout(0)
            ->marker(15)
            ->types(['message_created', 'message_callback'])
            ->get()
    )->toBe([
        'updates' => [['update_type' => 'message_created']],
        'marker' => 16,
    ]);
});
