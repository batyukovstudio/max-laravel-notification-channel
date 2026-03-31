<?php

declare(strict_types=1);

namespace NotificationChannels\Max;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Notification;
use NotificationChannels\Max\Containers\MessengerSection\Channel\Actions\SendMaxNotificationAction;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;

final class MaxChannel
{
    public function __construct(
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * @return array<string, mixed>|null
     *
     * @throws CouldNotSendNotification
     */
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        return app(SendMaxNotificationAction::class, [
            'dispatcher' => $this->dispatcher,
        ])->run($notifiable, $notification);
    }
}
