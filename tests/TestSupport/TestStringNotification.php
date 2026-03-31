<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\TestSupport;

use Illuminate\Notifications\Notification;

final class TestStringNotification extends Notification
{
    public function toMax($notifiable): string
    {
        return 'String-based MAX notification';
    }
}
