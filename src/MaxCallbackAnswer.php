<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use NotificationChannels\Max\Containers\MessengerSection\Callback\Actions\AnswerMaxCallbackAction;

final class MaxCallbackAnswer extends MaxBase
{
    private ?MaxMessage $message = null;

    private ?string $notification = null;

    public function __construct(
        private readonly string $callbackId,
        ?MaxClient $client = null
    ) {
        parent::__construct($client ?? app(MaxClient::class));
    }

    public static function create(string $callbackId): self
    {
        return new self($callbackId);
    }

    public function callbackId(): string
    {
        return $this->callbackId;
    }

    public function message(MaxMessage $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function notification(string $notification): self
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function send(): array
    {
        return app(AnswerMaxCallbackAction::class)->run($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function toBody(): array
    {
        $body = [];

        if ($this->message !== null) {
            $body['message'] = $this->message->toBody();
        }

        if ($this->notification !== null) {
            $body['notification'] = $this->notification;
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'callback_id' => $this->callbackId,
            'body' => $this->toBody(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
