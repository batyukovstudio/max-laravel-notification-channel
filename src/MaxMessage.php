<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use Illuminate\Support\Facades\View;
use NotificationChannels\Max\Ship\Contracts\MaxSenderContract;
use NotificationChannels\Max\Ship\Traits\HasSharedMessageLogic;
use NotificationChannels\Max\Ship\Traits\InteractsWithMaxMedia;

final class MaxMessage extends MaxBase implements MaxSenderContract
{
    use HasSharedMessageLogic;
    use InteractsWithMaxMedia;

    private ?string $text = null;

    public function __construct(?string $content = null, ?MaxClient $client = null)
    {
        parent::__construct($client ?? app(MaxClient::class));

        if ($content !== null) {
            $this->text($content);
        }
    }

    public static function create(?string $content = null): self
    {
        return new self($content);
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function content(string $content): self
    {
        return $this->text($content);
    }

    public function line(string $content): self
    {
        $current = $this->text ?? '';
        $this->text = $current.$content."\n";

        return $this;
    }

    public function lineIf(bool $condition, string $line): self
    {
        if ($condition) {
            $this->line($line);
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $mergeData
     */
    public function view(string $view, array $data = [], array $mergeData = []): self
    {
        return $this->text(View::make($view, $data, $mergeData)->render());
    }

    public function link(string $type, string $messageId): self
    {
        $this->body['link'] = [
            'type' => $type,
            'mid' => $messageId,
        ];

        return $this;
    }

    public function replyTo(string $messageId): self
    {
        return $this->link('reply', $messageId);
    }

    public function forward(string $messageId): self
    {
        return $this->link('forward', $messageId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function send(): ?array
    {
        if (! $this->canSend()) {
            return null;
        }

        $response = $this->clientWithOverrides()->sendMessage($this);

        return $response === null ? null : MaxClient::decodeResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function toBody(): array
    {
        $body = $this->bodySnapshot();

        if ($this->text !== null) {
            $body['text'] = $this->text;
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'query' => $this->toQuery(),
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
