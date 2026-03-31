<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use NotificationChannels\Max\Containers\MessengerSection\Subscription\Actions\CreateSubscriptionAction;
use NotificationChannels\Max\Containers\MessengerSection\Subscription\Actions\DeleteSubscriptionAction;
use NotificationChannels\Max\Containers\MessengerSection\Subscription\Actions\GetSubscriptionsAction;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;

final class MaxSubscription extends MaxBase
{
    /** @var array<string, mixed> */
    private array $payload = [];

    public static function create(?string $url = null): self
    {
        return (new self(app(MaxClient::class)))->whenUrl($url);
    }

    public function whenUrl(?string $url): self
    {
        if ($url !== null) {
            $this->url($url);
        }

        return $this;
    }

    public function url(string $url): self
    {
        $this->payload['url'] = $url;

        return $this;
    }

    /**
     * @param  list<string>  $types
     */
    public function updateTypes(array $types): self
    {
        $this->payload['update_types'] = $types;

        return $this;
    }

    public function secret(string $secret): self
    {
        $this->payload['secret'] = $secret;

        return $this;
    }

    public function payload(array $payload): self
    {
        $this->payload = [...$this->payload, ...$payload];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return app(GetSubscriptionsAction::class)->run($this->clientWithOverrides());
    }

    /**
     * @return array<string, mixed>
     */
    public function subscribe(): array
    {
        $this->ensureUrlPresent($this->payload['url'] ?? null);

        return app(CreateSubscriptionAction::class)->run($this->clientWithOverrides(), $this->payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function unsubscribe(?string $url = null): array
    {
        $targetUrl = $url ?? ($this->payload['url'] ?? '');
        $this->ensureUrlPresent($targetUrl);

        return app(DeleteSubscriptionAction::class)->run($this->clientWithOverrides(), (string) $targetUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function ensureUrlPresent(mixed $url): void
    {
        if (! is_string($url) || trim($url) === '') {
            throw CouldNotSendNotification::missingSubscriptionUrl();
        }
    }
}
