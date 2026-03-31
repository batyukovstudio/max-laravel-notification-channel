<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Subscription\Actions;

use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class GetSubscriptionsAction
{
    /**
     * @return array<string, mixed>
     */
    public function run(MaxClient $client): array
    {
        $response = $client->transport()->request('GET', '/subscriptions');

        return MaxTransport::decodeResponse($response);
    }
}
