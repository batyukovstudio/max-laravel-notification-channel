<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use GuzzleHttp\Client as HttpClient;
use NotificationChannels\Max\Containers\MessengerSection\Message\Actions\SendMaxMessageAction;
use NotificationChannels\Max\Ship\Enums\UploadType;
use NotificationChannels\Max\Ship\Http\MaxTransport;
use Psr\Http\Message\ResponseInterface;

class MaxClient
{
    private MaxTransport $transport;

    public function __construct(
        ?string $token = null,
        HttpClient $http = new HttpClient,
        ?string $baseUri = null
    ) {
        $this->transport = new MaxTransport($token, $http, $baseUri);
    }

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->transport = $this->transport->withToken($token);

        return $clone;
    }

    public function getToken(): ?string
    {
        return $this->transport->getToken();
    }

    public function getBaseUri(): string
    {
        return $this->transport->getBaseUri();
    }

    public function setToken(string $token): self
    {
        $this->transport->setToken($token);

        return $this;
    }

    public function setHttpClient(HttpClient $http): self
    {
        $this->transport->setHttpClient($http);

        return $this;
    }

    public function transport(): MaxTransport
    {
        return $this->transport;
    }

    public function sendMessage(MaxMessage $message): ?ResponseInterface
    {
        return app(SendMaxMessageAction::class)->run($message, $this);
    }

    /**
     * @param  array<string, mixed>|MaxMessage  $message
     */
    public function editMessage(string $messageId, array|MaxMessage $message): ?ResponseInterface
    {
        $body = $message instanceof MaxMessage ? $message->toBody() : $message;

        return $this->transport->request('PUT', '/messages', [
            'message_id' => $messageId,
        ], $body);
    }

    public function deleteMessage(string $messageId): ?ResponseInterface
    {
        return $this->transport->request('DELETE', '/messages', [
            'message_id' => $messageId,
        ]);
    }

    public function getMessage(string $messageId): ?ResponseInterface
    {
        return $this->transport->request('GET', "/messages/{$messageId}");
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function getUpdates(array $options = []): ?ResponseInterface
    {
        return $this->transport->request('GET', '/updates', $options);
    }

    public function getSubscriptions(): ?ResponseInterface
    {
        return $this->transport->request('GET', '/subscriptions');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function subscribe(array $payload): ?ResponseInterface
    {
        return $this->transport->request('POST', '/subscriptions', [], $payload);
    }

    public function unsubscribe(string $url): ?ResponseInterface
    {
        return $this->transport->request('DELETE', '/subscriptions', [
            'url' => $url,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function answerCallback(string $callbackId, array $payload): ?ResponseInterface
    {
        return $this->transport->request('POST', '/answers', [
            'callback_id' => $callbackId,
        ], $payload);
    }

    public function createUpload(UploadType $type): ?ResponseInterface
    {
        return $this->transport->request('POST', '/uploads', [
            'type' => $type->value,
        ]);
    }

    public function uploadFile(string $uploadUrl, string $filePath): ?ResponseInterface
    {
        return $this->transport->upload($uploadUrl, $filePath);
    }

    public function me(): ?ResponseInterface
    {
        return $this->transport->request('GET', '/me');
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeResponse(ResponseInterface $response): array
    {
        return MaxTransport::decodeResponse($response);
    }
}
