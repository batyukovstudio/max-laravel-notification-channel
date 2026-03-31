<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use Closure;
use JsonSerializable;

abstract class MaxBase implements JsonSerializable
{
    public MaxClient $client;

    public ?string $token = null;

    public ?Closure $exceptionHandler = null;

    protected ?bool $sendCondition = null;

    public function __construct(?MaxClient $client = null)
    {
        $this->client = $client ?? app(MaxClient::class);
    }

    public function withClient(MaxClient $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function token(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function hasToken(): bool
    {
        return $this->token !== null;
    }

    public function onError(callable $callback): static
    {
        $this->exceptionHandler = $callback instanceof Closure
            ? $callback
            : Closure::fromCallable($callback);

        return $this;
    }

    public function sendWhen(bool|callable $condition): static
    {
        $this->sendCondition = is_callable($condition)
            ? (bool) $condition()
            : $condition;

        return $this;
    }

    public function canSend(): bool
    {
        return $this->sendCondition ?? true;
    }

    public function clientWithOverrides(): MaxClient
    {
        return $this->hasToken()
            ? $this->client->withToken($this->token)
            : $this->client;
    }
}
