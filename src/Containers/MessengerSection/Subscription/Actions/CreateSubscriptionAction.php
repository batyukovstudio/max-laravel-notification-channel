<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Subscription\Actions;

use NotificationChannels\Max\MaxClient;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class CreateSubscriptionAction
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function run(MaxClient $client, array $payload): array
    {
        $response = $client->transport()->request('POST', '/subscriptions', [], $payload);

        return MaxTransport::decodeResponse($response);
    }
}
