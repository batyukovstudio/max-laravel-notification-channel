<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Http;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Utils;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

final class MaxTransport
{
    public const DEFAULT_BASE_URI = 'https://platform-api.max.ru';

    private string $baseUri;

    public function __construct(
        private ?string $token = null,
        private HttpClient $http = new HttpClient,
        ?string $baseUri = null
    ) {
        $this->setBaseUri($baseUri ?? self::DEFAULT_BASE_URI);
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function withToken(string $token): self
    {
        $clone = clone $this;
        $clone->token = $token;

        return $clone;
    }

    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    public function setBaseUri(string $baseUri): self
    {
        $this->baseUri = rtrim($baseUri, '/');

        return $this;
    }

    public function setHttpClient(HttpClient $http): self
    {
        $this->http = $http;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $json
     *
     * @throws CouldNotSendNotification
     */
    public function request(
        string $method,
        string $uri,
        array $query = [],
        array $json = []
    ): ?ResponseInterface {
        $options = $this->defaultOptions();

        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($json !== []) {
            $options['json'] = $json;
        }

        try {
            return $this->http->request($method, $this->normalizeUri($uri), $options);
        } catch (BadResponseException $exception) {
            throw CouldNotSendNotification::maxRespondedWithAnError(
                $exception->getResponse()->getStatusCode(),
                $this->resolveErrorMessage($exception),
                $exception
            );
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithMax($exception->getMessage(), $exception);
        }
    }

    /**
     * @throws CouldNotSendNotification
     */
    public function upload(string $uri, string $filePath): ?ResponseInterface
    {
        if (! is_file($filePath)) {
            throw CouldNotSendNotification::invalidFile($filePath);
        }

        $stream = fopen($filePath, 'rb');

        if ($stream === false) {
            throw CouldNotSendNotification::fileAccessFailed($filePath);
        }

        $options = $this->defaultOptions();
        $options['multipart'] = [[
            'name' => 'data',
            'contents' => $stream,
            'filename' => basename($filePath),
        ]];

        try {
            return $this->http->request('POST', $this->normalizeUri($uri), $options);
        } catch (BadResponseException $exception) {
            throw CouldNotSendNotification::maxRespondedWithAnError(
                $exception->getResponse()->getStatusCode(),
                $this->resolveErrorMessage($exception),
                $exception
            );
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithMax($exception->getMessage(), $exception);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CouldNotSendNotification
     */
    public static function decodeResponse(ResponseInterface $response): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = Utils::jsonDecode($response->getBody()->getContents(), true);

            return $decoded;
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithMax($exception->getMessage(), $exception);
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws CouldNotSendNotification
     */
    private function defaultOptions(): array
    {
        if ($this->token === null || trim($this->token) === '') {
            throw CouldNotSendNotification::maxBotTokenNotProvided(
                'You must provide your MAX bot token to make API requests.'
            );
        }

        return [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => $this->token,
            ],
        ];
    }

    private function normalizeUri(string $uri): string
    {
        if (preg_match('#^https?://#', $uri) === 1) {
            return $uri;
        }

        return $this->baseUri.'/'.ltrim($uri, '/');
    }

    private function resolveErrorMessage(BadResponseException $exception): string
    {
        $body = (string) $exception->getResponse()->getBody();
        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $message = $decoded['message'] ?? $decoded['description'] ?? $decoded['code'] ?? null;

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return $body !== '' ? $body : 'no description given';
    }
}
