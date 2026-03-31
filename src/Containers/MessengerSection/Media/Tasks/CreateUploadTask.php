<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Media\Tasks;

use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Enums\UploadType;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class CreateUploadTask
{
    /**
     * @return array<string, mixed>
     */
    public function run(MaxClient $client, UploadType $type): array
    {
        $response = $client->transport()->request('POST', '/uploads', [
            'type' => $type->value,
        ]);

        return MaxTransport::decodeResponse($response);
    }
}
