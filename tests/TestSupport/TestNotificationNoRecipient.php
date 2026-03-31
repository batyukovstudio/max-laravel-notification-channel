<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Tests\TestSupport;

use Illuminate\Notifications\Notification;
use NotificationChannels\Max\MaxMessage;

final class TestNotificationNoRecipient extends Notification
{
    public function toMax($notifiable): MaxMessage
    {
        return MaxMessage::create('MAX notifications are production-ready.');
    }
}
