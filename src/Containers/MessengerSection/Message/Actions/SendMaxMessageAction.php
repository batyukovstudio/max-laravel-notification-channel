<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Message\Actions;

use NotificationChannels\Max\Containers\MessengerSection\Message\Tasks\BuildMessageBodyTask;
use NotificationChannels\Max\Containers\MessengerSection\Message\Tasks\BuildMessageQueryTask;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\MaxMessage;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;
use Psr\Http\Message\ResponseInterface;

final class SendMaxMessageAction
{
    private const INITIAL_ATTACHMENT_DELAY_US = 100000;

    /** @var list<int> */
    private const ATTACHMENT_READY_RETRY_DELAYS_US = [200000, 400000, 800000];

    public function __construct(
        private readonly BuildMessageQueryTask $buildMessageQueryTask,
        private readonly BuildMessageBodyTask $buildMessageBodyTask
    ) {}

    public function run(MaxMessage $message, MaxClient $client): ?ResponseInterface
    {
        $query = $this->buildMessageQueryTask->run($message);
        $body = $this->buildMessageBodyTask->run($message);

        if (! $this->hasAttachments($body)) {
            return $client->transport()->request('POST', '/messages', $query, $body);
        }

        usleep(self::INITIAL_ATTACHMENT_DELAY_US);

        $attempt = 0;
        $lastException = null;

        while ($attempt <= count(self::ATTACHMENT_READY_RETRY_DELAYS_US)) {
            try {
                return $client->transport()->request('POST', '/messages', $query, $body);
            } catch (CouldNotSendNotification $exception) {
                if (! $exception->isAttachmentNotReady()) {
                    throw $exception;
                }

                $lastException = $exception;
                $delay = self::ATTACHMENT_READY_RETRY_DELAYS_US[$attempt] ?? null;
                if ($delay === null) {
                    throw $lastException;
                }

                usleep($delay);
                $attempt++;
            }
        }

        throw $lastException ?? CouldNotSendNotification::couldNotCommunicateWithMax('Attachment upload retry failed.');
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function hasAttachments(array $body): bool
    {
        return isset($body['attachments']) && is_array($body['attachments']) && $body['attachments'] !== [];
    }
}
