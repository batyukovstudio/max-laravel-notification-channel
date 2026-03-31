<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use Closure;
use JsonSerializable;

abstract class MaxBase implements JsonSerializable
{
    private static ?self $pendingExceptionTarget = null;

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
        self::$pendingExceptionTarget = $this;

        return $this;
    }

    public static function pullPendingExceptionTarget(): ?self
    {
        $target = self::$pendingExceptionTarget;
        self::$pendingExceptionTarget = null;

        return $target;
    }

    public static function forgetPendingExceptionTarget(): void
    {
        self::$pendingExceptionTarget = null;
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
        $token = $this->token;

        return $token !== null
            ? $this->client->withToken($token)
            : $this->client;
    }
}
