<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Mockery;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;

it('uploads image attachments through MAX upload endpoints', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'POST' && $uri === 'https://platform-api.max.ru/uploads' && $options['query'] === ['type' => 'image'])
        ->andReturn(new Response(200, [], json_encode([
            'url' => 'https://upload.max.test/image',
        ])));
    $http->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options): bool {
            return $method === 'POST'
                && $uri === 'https://upload.max.test/image'
                && $options['multipart'][0]['name'] === 'data'
                && $options['multipart'][0]['filename'] !== '';
        })
        ->andReturn(new Response(200, [], json_encode([
            'token' => 'image-token',
        ])));

    $file = tempnam(sys_get_temp_dir(), 'max-image');
    file_put_contents($file, 'binary');

    $message = new MaxMessage(client: new MaxClient('token', $http));
    $message->image($file);

    expect($message->toBody()['attachments'])->toBe([
        [
            'type' => 'image',
            'payload' => ['token' => 'image-token'],
        ],
    ]);

    unlink($file);
});

it('keeps the upload token for MAX video attachments', function () {
    $http = Mockery::mock(HttpClient::class);
    $http->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options): bool => $method === 'POST' && $uri === 'https://platform-api.max.ru/uploads' && $options['query'] === ['type' => 'video'])
        ->andReturn(new Response(200, [], json_encode([
            'url' => 'https://upload.max.test/video',
            'token' => 'video-token',
        ])));
    $http->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options): bool {
            return $method === 'POST'
                && $uri === 'https://upload.max.test/video'
                && $options['multipart'][0]['name'] === 'data';
        })
        ->andReturn(new Response(200, [], json_encode([
            'status' => 'uploaded',
        ])));

    $file = tempnam(sys_get_temp_dir(), 'max-video');
    file_put_contents($file, 'binary');

    $message = new MaxMessage(client: new MaxClient('token', $http));
    $message->video($file);

    expect($message->toBody()['attachments'])->toBe([
        [
            'type' => 'video',
            'payload' => ['token' => 'video-token'],
        ],
    ]);

    unlink($file);
});
