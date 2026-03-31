<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\TestSupport;

use Illuminate\Notifications\Notification;

final class TestNotifiable
{
    public function __construct(
        public int|string|null $maxUserId = 67890,
        public int|string|null $maxChatId = null
    ) {}

    public function routeNotificationFor(string $driver, Notification $notification): mixed
    {
        if ($driver !== 'max') {
            return null;
        }

        if ($this->maxChatId !== null) {
            return ['chat_id' => $this->maxChatId];
        }

        return $this->maxUserId;
    }
}
