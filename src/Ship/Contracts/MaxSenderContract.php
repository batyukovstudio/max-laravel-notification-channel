<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Contracts;

use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;

interface MaxSenderContract
{
    /**
     * @return array<string, mixed>|null
     *
     * @throws CouldNotSendNotification
     */
    public function send(): ?array;
}
