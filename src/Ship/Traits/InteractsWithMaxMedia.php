<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Traits;

use NotificationChannels\Max\Containers\MessengerSection\Media\Actions\UploadAttachmentAction;
use NotificationChannels\Max\Ship\Enums\UploadType;

trait InteractsWithMaxMedia
{
    public function image(string $filePath): static
    {
        return $this->attachment(
            app(UploadAttachmentAction::class)->run($this->clientWithOverrides(), $filePath, UploadType::Image)
        );
    }

    public function photo(string $filePath): static
    {
        return $this->image($filePath);
    }

    public function video(string $filePath): static
    {
        return $this->attachment(
            app(UploadAttachmentAction::class)->run($this->clientWithOverrides(), $filePath, UploadType::Video)
        );
    }

    public function audio(string $filePath): static
    {
        return $this->attachment(
            app(UploadAttachmentAction::class)->run($this->clientWithOverrides(), $filePath, UploadType::Audio)
        );
    }

    public function file(string $filePath): static
    {
        return $this->attachment(
            app(UploadAttachmentAction::class)->run($this->clientWithOverrides(), $filePath, UploadType::File)
        );
    }
}
