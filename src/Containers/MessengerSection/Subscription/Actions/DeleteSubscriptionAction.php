<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Subscription\Actions;

use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class DeleteSubscriptionAction
{
    /**
     * @return array<string, mixed>
     */
    public function run(MaxClient $client, string $url): array
    {
        $response = $client->transport()->request('DELETE', '/subscriptions', [
            'url' => $url,
        ]);

        return MaxTransport::decodeResponse($response);
    }
}
