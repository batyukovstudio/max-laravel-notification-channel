<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Containers\MessengerSection\Callback\Actions;

use NotificationChannels\Max\MaxCallbackAnswer;
use NotificationChannels\Max\Ship\Http\MaxTransport;

final class AnswerMaxCallbackAction
{
    /**
     * @return array<string, mixed>
     */
    public function run(MaxCallbackAnswer $answer): array
    {
        $response = $answer->clientWithOverrides()->transport()->request(
            'POST',
            '/answers',
            [
                'callback_id' => $answer->callbackId(),
            ],
            $answer->toBody()
        );

        return MaxTransport::decodeResponse($response);
    }
}
