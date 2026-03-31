<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Traits;

use NotificationChannels\Max\Containers\MessengerSection\Media\Actions\UploadAttachmentAction;
use NotificationChannels\Max\Ship\Enums\UploadType;

trait InteractsWithMaxMedia
{
    /**
     * @var list<array{file_path: string, type: UploadType}>
     */
    protected array $pendingMaxMedia = [];

    public function image(string $filePath): static
    {
        return $this->queueAttachmentUpload($filePath, UploadType::Image);
    }

    public function photo(string $filePath): static
    {
        return $this->image($filePath);
    }

    public function video(string $filePath): static
    {
        return $this->queueAttachmentUpload($filePath, UploadType::Video);
    }

    public function audio(string $filePath): static
    {
        return $this->queueAttachmentUpload($filePath, UploadType::Audio);
    }

    public function file(string $filePath): static
    {
        return $this->queueAttachmentUpload($filePath, UploadType::File);
    }

    protected function resolvePendingMaxMedia(): void
    {
        if ($this->pendingMaxMedia === []) {
            return;
        }

        foreach ($this->pendingMaxMedia as $pendingAttachment) {
            $this->attachment(
                app(UploadAttachmentAction::class)->run(
                    $this->clientWithOverrides(),
                    $pendingAttachment['file_path'],
                    $pendingAttachment['type']
                )
            );
        }

        $this->pendingMaxMedia = [];
    }

    private function queueAttachmentUpload(string $filePath, UploadType $type): static
    {
        $this->pendingMaxMedia[] = [
            'file_path' => $filePath,
            'type' => $type,
        ];

        return $this;
    }
}
