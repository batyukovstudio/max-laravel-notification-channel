<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;
use RuntimeException;

it('decodes valid MAX responses', function () {
    $response = new Response(200, [], json_encode([
        'message' => ['body' => ['mid' => 'message-5']],
    ]));

    expect(MaxClient::decodeResponse($response))->toBe([
        'message' => ['body' => ['mid' => 'message-5']],
    ]);
});

it('uses and normalizes the MAX api base uri', function () {
    expect((new MaxClient)->getBaseUri())->toBe('https://platform-api.max.ru')
        ->and((new MaxClient(baseUri: 'https://example.com///'))->getBaseUri())->toBe('https://example.com');
});

it('sends messages through the MAX messages endpoint', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options): bool {
            return $method === 'POST'
                && $uri === 'https://platform-api.max.ru/messages'
                && $options['headers']['Authorization'] === 'token'
                && $options['query'] === [
                    'user_id' => 12345,
                    'disable_link_preview' => true,
                ]
                && $options['json'] === [
                    'format' => 'markdown',
                    'text' => 'Hello MAX',
                ];
        })
        ->andReturn(new Response(200, [], json_encode([
            'message' => ['body' => ['mid' => 'message-6']],
        ])));

    $client = new MaxClient('token', $http);
    $message = MaxMessage::create('Hello MAX')
        ->toUser(12345)
        ->disableLinkPreview()
        ->markdown();

    expect($client->sendMessage($message))->toBeInstanceOf(Response::class);
});

it('supports message management, subscriptions, updates and callbacks', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'PUT' && $uri === 'https://platform-api.max.ru/messages' && $options['query'] === ['message_id' => 'message-1'] && $options['json'] === ['text' => 'Updated'])->andReturn(new Response(200, [], json_encode(['success' => true])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'DELETE' && $uri === 'https://platform-api.max.ru/messages' && $options['query'] === ['message_id' => 'message-1'])->andReturn(new Response(200, [], json_encode(['success' => true])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri): bool => $method === 'GET' && $uri === 'https://platform-api.max.ru/messages/message-1')->andReturn(new Response(200, [], json_encode(['message' => ['body' => ['mid' => 'message-1']]])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'GET' && $uri === 'https://platform-api.max.ru/updates' && $options['query'] === ['limit' => 2])->andReturn(new Response(200, [], json_encode(['updates' => [], 'marker' => 10])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri): bool => $method === 'GET' && $uri === 'https://platform-api.max.ru/subscriptions')->andReturn(new Response(200, [], json_encode(['subscriptions' => []])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'POST' && $uri === 'https://platform-api.max.ru/subscriptions' && $options['json'] === ['url' => 'https://example.com/webhook'])->andReturn(new Response(200, [], json_encode(['success' => true])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'DELETE' && $uri === 'https://platform-api.max.ru/subscriptions' && $options['query'] === ['url' => 'https://example.com/webhook'])->andReturn(new Response(200, [], json_encode(['success' => true])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'POST' && $uri === 'https://platform-api.max.ru/answers' && $options['query'] === ['callback_id' => 'callback-1'] && $options['json'] === ['notification' => 'Done'])->andReturn(new Response(200, [], json_encode(['success' => true])));
    $http->shouldReceive('request')->once()->withArgs(fn (string $method, string $uri): bool => $method === 'GET' && $uri === 'https://platform-api.max.ru/me')->andReturn(new Response(200, [], json_encode(['user_id' => 1])));

    $client = new MaxClient('token', $http);

    expect($client->editMessage('message-1', ['text' => 'Updated']))->toBeInstanceOf(Response::class)
        ->and($client->deleteMessage('message-1'))->toBeInstanceOf(Response::class)
        ->and($client->getMessage('message-1'))->toBeInstanceOf(Response::class)
        ->and($client->getUpdates(['limit' => 2]))->toBeInstanceOf(Response::class)
        ->and($client->getSubscriptions())->toBeInstanceOf(Response::class)
        ->and($client->subscribe(['url' => 'https://example.com/webhook']))->toBeInstanceOf(Response::class)
        ->and($client->unsubscribe('https://example.com/webhook'))->toBeInstanceOf(Response::class)
        ->and($client->answerCallback('callback-1', ['notification' => 'Done']))->toBeInstanceOf(Response::class)
        ->and($client->me())->toBeInstanceOf(Response::class);
});

it('throws when the MAX bot token is missing', function () {
    (new MaxClient)->sendMessage(MaxMessage::create('Hello')->toUser(12345));
})->throws(CouldNotSendNotification::class, 'You must provide your MAX bot token to make API requests.');

it('wraps MAX client exceptions', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->andThrow(new ClientException(
            'Bad Request',
            new Request('POST', 'https://platform-api.max.ru/messages'),
            new Response(400, [], json_encode(['message' => 'chat not found']))
        ));

    $client = new MaxClient('token', $http);
    $message = MaxMessage::create('Hello')->toUser(12345);

    $client->sendMessage($message);
})->throws(CouldNotSendNotification::class, 'MAX responded with an error `400 - chat not found`');

it('wraps generic transport exceptions', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->andThrow(new RuntimeException('Connection refused'));

    $client = new MaxClient('token', $http);
    $message = MaxMessage::create('Hello')->toUser(12345);

    $client->sendMessage($message);
})->throws(CouldNotSendNotification::class, 'The communication with MAX failed. `Connection refused`');
