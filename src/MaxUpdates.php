<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use NotificationChannels\Max\Containers\MessengerSection\Update\Actions\GetUpdatesAction;

final class MaxUpdates extends MaxBase
{
    /** @var array<string, mixed> */
    private array $options = [];

    public static function create(): self
    {
        return new self(app(MaxClient::class));
    }

    public function limit(int $limit): self
    {
        $this->options['limit'] = $limit;

        return $this;
    }

    public function timeout(int $timeout): self
    {
        $this->options['timeout'] = $timeout;

        return $this;
    }

    public function marker(int|string $marker): self
    {
        $this->options['marker'] = $marker;

        return $this;
    }

    /**
     * @param  list<string>  $types
     */
    public function types(array $types): self
    {
        $this->options['types'] = implode(',', $types);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): self
    {
        $this->options = [...$this->options, ...$options];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return app(GetUpdatesAction::class)->run($this->clientWithOverrides(), $this->options);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
