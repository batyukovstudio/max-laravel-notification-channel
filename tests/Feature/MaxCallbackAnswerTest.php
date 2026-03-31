<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Mockery;
use NotificationChannels\Max\MaxCallbackAnswer;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;

it('answers MAX callbacks with notification and updated message', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options): bool {
            return $method === 'POST'
                && $uri === 'https://platform-api.max.ru/answers'
                && $options['query'] === ['callback_id' => 'callback-42']
                && $options['json'] === [
                    'message' => ['text' => 'Updated'],
                    'notification' => 'Done',
                ];
        })
        ->andReturn(new Response(200, [], json_encode(['success' => true])));

    $answer = new MaxCallbackAnswer('callback-42', new MaxClient('token', $http));
    $answer
        ->message(MaxMessage::create('Updated'))
        ->notification('Done');

    expect($answer->send())->toBe(['success' => true]);
});
