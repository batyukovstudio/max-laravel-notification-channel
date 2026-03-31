<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Traits;

use NotificationChannels\Max\Ship\Enums\TextFormat;

trait HasSharedMessageLogic
{
    /** @var array<string, mixed> */
    protected array $query = [];

    /** @var array<string, mixed> */
    protected array $body = [];

    /** @var list<list<array<string, mixed>>> */
    protected array $buttonRows = [];

    protected bool $attachmentsDefined = false;

    public function to(int|string $userId): static
    {
        $this->query['user_id'] = $userId;
        unset($this->query['chat_id']);

        return $this;
    }

    public function toUser(int|string $userId): static
    {
        return $this->to($userId);
    }

    public function toChat(int|string $chatId): static
    {
        $this->query['chat_id'] = $chatId;
        unset($this->query['user_id']);

        return $this;
    }

    public function disableLinkPreview(bool $disable = true): static
    {
        // MAX disables preview when the flag is explicitly false.
        $this->query['disable_link_preview'] = ! $disable;

        return $this;
    }

    public function queryOptions(array $options): static
    {
        $this->query = [...$this->query, ...$options];

        return $this;
    }

    public function bodyOptions(array $options): static
    {
        $this->body = [...$this->body, ...$options];

        return $this;
    }

    public function plain(): static
    {
        unset($this->body['format']);

        return $this;
    }

    public function markdown(): static
    {
        return $this->format(TextFormat::Markdown);
    }

    public function html(): static
    {
        return $this->format(TextFormat::Html);
    }

    public function format(TextFormat|string $format): static
    {
        $this->body['format'] = $format instanceof TextFormat ? $format->value : $format;

        return $this;
    }

    public function notify(bool $notify = true): static
    {
        $this->body['notify'] = $notify;

        return $this;
    }

    public function silent(bool $silent = true): static
    {
        return $this->notify(! $silent);
    }

    /**
     * @param  array<string, mixed>  $attachment
     */
    public function attachment(array $attachment): static
    {
        $attachments = $this->body['attachments'] ?? [];
        $attachments[] = $attachment;
        $this->body['attachments'] = $attachments;
        $this->attachmentsDefined = true;

        return $this;
    }

    /**
     * @param  list<array<string, mixed>>  $attachments
     */
    public function attachments(array $attachments): static
    {
        $this->body['attachments'] = $attachments;
        $this->attachmentsDefined = true;

        return $this;
    }

    /**
     * @param  list<list<array<string, mixed>>>  $buttons
     */
    public function inlineKeyboard(array $buttons): static
    {
        $this->buttonRows = $buttons;

        return $this->syncInlineKeyboardAttachment();
    }

    public function button(string $text, string $url, int $columns = 2): static
    {
        return $this->appendButton([
            'type' => 'link',
            'text' => $text,
            'url' => $url,
        ], $columns);
    }

    public function buttonWithCallback(string $text, string $payload, int $columns = 2): static
    {
        return $this->appendButton([
            'type' => 'callback',
            'text' => $text,
            'payload' => $payload,
        ], $columns);
    }

    public function buttonRequestContact(string $text, int $columns = 2): static
    {
        return $this->appendButton([
            'type' => 'request_contact',
            'text' => $text,
        ], $columns);
    }

    public function buttonRequestGeoLocation(string $text, int $columns = 2): static
    {
        return $this->appendButton([
            'type' => 'request_geo_location',
            'text' => $text,
        ], $columns);
    }

    public function buttonOpenApp(
        string $text,
        ?string $webApp = null,
        ?int $contactId = null,
        int $columns = 2
    ): static {
        $button = [
            'type' => 'open_app',
            'text' => $text,
        ];

        if ($webApp !== null) {
            $button['web_app'] = $webApp;
        }

        if ($contactId !== null) {
            $button['contact_id'] = $contactId;
        }

        return $this->appendButton($button, $columns);
    }

    public function buttonMessage(string $text, int $columns = 2): static
    {
        return $this->appendButton([
            'type' => 'message',
            'text' => $text,
        ], $columns);
    }

    public function hasRecipient(): bool
    {
        return isset($this->query['user_id']) || isset($this->query['chat_id']);
    }

    public function getQueryValue(string $key): mixed
    {
        return $this->query[$key] ?? null;
    }

    public function getBodyValue(string $key): mixed
    {
        return $this->body[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toQuery(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    protected function bodySnapshot(): array
    {
        $body = $this->body;

        if (! $this->attachmentsDefined) {
            unset($body['attachments']);
        }

        return $body;
    }

    private function appendButton(array $button, int $columns): static
    {
        $columns = max(1, $columns);
        $flattened = [];

        foreach ($this->buttonRows as $row) {
            $flattened = [...$flattened, ...$row];
        }

        $flattened[] = $button;
        $this->buttonRows = array_chunk($flattened, $columns);

        return $this->syncInlineKeyboardAttachment();
    }

    private function syncInlineKeyboardAttachment(): static
    {
        $attachments = array_values(array_filter(
            $this->body['attachments'] ?? [],
            static fn (array $attachment): bool => ($attachment['type'] ?? null) !== 'inline_keyboard'
        ));

        if ($this->buttonRows !== []) {
            $attachments[] = [
                'type' => 'inline_keyboard',
                'payload' => [
                    'buttons' => $this->buttonRows,
                ],
            ];
        }

        $this->body['attachments'] = $attachments;
        $this->attachmentsDefined = true;

        return $this;
    }
}
