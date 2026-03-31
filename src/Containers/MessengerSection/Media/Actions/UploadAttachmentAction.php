<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Media\Actions;

use NotificationChannels\Max\Containers\MessengerSection\Media\Tasks\CreateUploadTask;
use NotificationChannels\Max\Containers\MessengerSection\Media\Tasks\UploadFileTask;
use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Enums\UploadType;
use NotificationChannels\Max\Ship\Exceptions\CouldNotSendNotification;

final class UploadAttachmentAction
{
    public function __construct(
        private readonly CreateUploadTask $createUploadTask,
        private readonly UploadFileTask $uploadFileTask
    ) {}

    /**
     * @return array{type: string, payload: array<string, mixed>}
     *
     * @throws CouldNotSendNotification
     */
    public function run(MaxClient $client, string $filePath, UploadType $type): array
    {
        $upload = $this->createUploadTask->run($client, $type);
        $uploadUrl = $upload['url'] ?? null;

        if (! is_string($uploadUrl) || $uploadUrl === '') {
            throw CouldNotSendNotification::missingUploadUrl();
        }

        $uploaded = $this->uploadFileTask->run($client, $uploadUrl, $filePath);

        $payload = match ($type) {
            UploadType::Video, UploadType::Audio => [
                'token' => $upload['token'] ?? $uploaded['token'] ?? throw CouldNotSendNotification::missingUploadToken($type->value),
            ],
            default => $uploaded,
        };

        return [
            'type' => $type->value,
            'payload' => $payload,
        ];
    }
}
