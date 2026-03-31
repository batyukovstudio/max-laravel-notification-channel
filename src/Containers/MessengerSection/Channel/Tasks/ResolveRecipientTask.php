<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Channel\Tasks;

use Illuminate\Notifications\Notification;
use NotificationChannels\Max\MaxChannel;
use NotificationChannels\Max\MaxMessage;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;

final class ResolveRecipientTask
{
    /**
     * @return array{user_id?: int|string, chat_id?: int|string}
     *
     * @throws CouldNotSendNotification
     */
    public function run(MaxMessage $message, mixed $notifiable, Notification $notification): array
    {
        if ($message->hasRecipient()) {
            $recipient = [];
            $userId = $message->getQueryValue('user_id');
            $chatId = $message->getQueryValue('chat_id');

            if (is_int($userId) || is_string($userId)) {
                $recipient['user_id'] = $userId;
            }

            if (is_int($chatId) || is_string($chatId)) {
                $recipient['chat_id'] = $chatId;
            }

            return $recipient;
        }

        if (! is_object($notifiable) || ! method_exists($notifiable, 'routeNotificationFor')) {
            throw CouldNotSendNotification::missingRecipient();
        }

        $route = $notifiable->routeNotificationFor('max', $notification)
            ?? $notifiable->routeNotificationFor(MaxChannel::class, $notification);

        return $this->normalizeRoute($route);
    }

    /**
     * @return array{user_id?: int|string, chat_id?: int|string}
     *
     * @throws CouldNotSendNotification
     */
    private function normalizeRoute(mixed $route): array
    {
        if (is_int($route) || is_string($route)) {
            return ['user_id' => $route];
        }

        if (is_array($route)) {
            $normalized = array_filter([
                'user_id' => $route['user_id'] ?? null,
                'chat_id' => $route['chat_id'] ?? null,
            ], static fn (mixed $value): bool => is_int($value) || is_string($value));

            if ($normalized !== []) {
                return $normalized;
            }
        }

        throw CouldNotSendNotification::missingRecipient();
    }
}
