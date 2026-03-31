<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\Feature;

use GuzzleHttp\Psr7\Response;
use Mockery;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;

it('builds MAX message query and body payloads', function () {
    $message = MaxMessage::create('Hello MAX')
        ->toUser(12345)
        ->disableLinkPreview()
        ->markdown()
        ->silent()
        ->button('Docs', 'https://dev.max.ru')
        ->buttonWithCallback('Confirm', 'confirm');

    expect($message->toQuery())->toBe([
        'user_id' => 12345,
        'disable_link_preview' => true,
    ])->and($message->toBody())->toBe([
        'format' => 'markdown',
        'notify' => false,
        'attachments' => [[
            'type' => 'inline_keyboard',
            'payload' => [
                'buttons' => [[
                    [
                        'type' => 'link',
                        'text' => 'Docs',
                        'url' => 'https://dev.max.ru',
                    ],
                    [
                        'type' => 'callback',
                        'text' => 'Confirm',
                        'payload' => 'confirm',
                    ],
                ]],
            ],
        ]],
        'text' => 'Hello MAX',
    ]);
});

it('supports line helpers and blade views', function () {
    $message = MaxMessage::create()
        ->line('Line 1')
        ->lineIf(true, 'Line 2');

    expect($message->toBody()['text'])->toBe("Line 1\nLine 2\n");

    $viewMessage = MaxMessage::create()->view('TestViewFile', [
        'name' => 'MAX',
    ]);

    expect($viewMessage->toBody()['text'])->toBe("<h1>Hello, MAX</h1>\n");
});

it('sends messages directly through the MAX client', function () {
    $client = Mockery::mock(MaxClient::class);
    $client
        ->shouldReceive('sendMessage')
        ->once()
        ->withArgs(fn (MaxMessage $message): bool => $message->toBody() === ['text' => 'Direct send'])
        ->andReturn(new Response(200, [], json_encode([
            'message' => ['body' => ['mid' => 'message-4']],
        ])));

    $message = new MaxMessage('Direct send', $client);

    expect($message->send())->toBe([
        'message' => ['body' => ['mid' => 'message-4']],
    ]);
});

it('keeps empty attachments array when explicitly requested', function () {
    $message = MaxMessage::create('Editable')->attachments([]);

    expect($message->toBody())->toBe([
        'attachments' => [],
        'text' => 'Editable',
    ]);
});
