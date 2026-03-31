<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Update\Actions;

use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class GetUpdatesAction
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function run(MaxClient $client, array $options = []): array
    {
        $response = $client->transport()->request('GET', '/updates', $options);

        return MaxTransport::decodeResponse($response);
    }
}
