<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Message\Tasks;

use NotificationChannels\Max\MaxMessage;

final class BuildMessageBodyTask
{
    /**
     * @return array<string, mixed>
     */
    public function run(MaxMessage $message): array
    {
        return $message->toBody();
    }
}
