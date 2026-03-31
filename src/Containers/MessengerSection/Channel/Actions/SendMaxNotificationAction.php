<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Channel\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use NotificationChannels\Max\Containers\MessengerSection\Channel\Tasks\ResolveRecipientTask;
use NotificationChannels\Max\MaxMessage;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;
use NotificationChannels\Max\Ship\Http\MaxTransport;
use Throwable;

final class SendMaxNotificationAction
{
    public function __construct(
        private readonly ResolveRecipientTask $resolveRecipientTask,
        private readonly Dispatcher $dispatcher
    ) {}

    /**
     * @return array<string, mixed>|null
     *
     * @throws Throwable
     */
    public function run(mixed $notifiable, Notification $notification): ?array
    {
        MaxMessage::forgetPendingExceptionTarget();

        if (! method_exists($notification, 'toMax')) {
            return null;
        }

        $message = null;
        $recipient = [];

        try {
            $message = $notification->toMax($notifiable);
            MaxMessage::forgetPendingExceptionTarget();

            if (is_string($message)) {
                $message = MaxMessage::create($message);
            }

            if (! $message instanceof MaxMessage) {
                throw CouldNotSendNotification::invalidMessage();
            }

            if (! $message->canSend()) {
                return null;
            }

            $recipient = $this->resolveRecipientTask->run($message, $notifiable, $notification);

            if (isset($recipient['user_id'])) {
                $message->toUser($recipient['user_id']);
            }

            if (isset($recipient['chat_id'])) {
                $message->toChat($recipient['chat_id']);
            }

            $response = $message->clientWithOverrides()->sendMessage($message);
        } catch (Throwable $exception) {
            $preparedMessage = $message instanceof MaxMessage
                ? $message
                : MaxMessage::pullPendingExceptionTarget();

            $data = [
                'to' => $recipient,
                'exception' => $exception,
            ];

            if ($preparedMessage instanceof MaxMessage) {
                $data['request'] = $preparedMessage->toArray();
                $preparedMessage->exceptionHandler?->__invoke($data);
            }

            $this->dispatcher->dispatch(
                new NotificationFailed($notifiable, $notification, 'max', $data)
            );

            throw $exception;
        }

        return $response === null ? null : MaxTransport::decodeResponse($response);
    }
}
