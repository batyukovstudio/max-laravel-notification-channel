<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Message\Actions;

use NotificationChannels\Max\Containers\MessengerSection\Message\Tasks\BuildMessageBodyTask;
use NotificationChannels\Max\Containers\MessengerSection\Message\Tasks\BuildMessageQueryTask;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;
use Psr\Http\Message\ResponseInterface;

final class SendMaxMessageAction
{
    public function __construct(
        private readonly BuildMessageQueryTask $buildMessageQueryTask,
        private readonly BuildMessageBodyTask $buildMessageBodyTask
    ) {}

    public function run(MaxMessage $message, MaxClient $client): ResponseInterface
    {
        return $client->transport()->request(
            'POST',
            '/messages',
            $this->buildMessageQueryTask->run($message),
            $this->buildMessageBodyTask->run($message)
        );
    }
}
