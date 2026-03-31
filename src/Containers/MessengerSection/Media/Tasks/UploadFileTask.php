<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Media\Tasks;

use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class UploadFileTask
{
    /**
     * @return array<string, mixed>
     */
    public function run(MaxClient $client, string $uploadUrl, string $filePath): array
    {
        $response = $client->transport()->upload($uploadUrl, $filePath);

        return MaxTransport::decodeResponse($response);
    }
}
