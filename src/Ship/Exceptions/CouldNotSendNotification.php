<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Exceptions;

use Exception;
use Throwable;

final class CouldNotSendNotification extends Exception
{
    public static function maxRespondedWithAnError(
        int $statusCode,
        string $message,
        ?Throwable $previous = null
    ): self {
        return new self(
            sprintf('MAX responded with an error `%d - %s`', $statusCode, $message),
            0,
            $previous
        );
    }

    public static function maxBotTokenNotProvided(string $message): self
    {
        return new self($message);
    }

    public static function couldNotCommunicateWithMax(string $message, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('The communication with MAX failed. `%s`', $message),
            0,
            $previous
        );
    }

    public static function invalidMessage(): self
    {
        return new self(
            'The toMax() method must return a string or an instance of '.\NotificationChannels\Max\MaxMessage::class
        );
    }

    public static function missingRecipient(): self
    {
        return new self(
            'No MAX recipient was provided. Set user_id/chat_id on the message or configure routeNotificationForMax().'
        );
    }

    public static function invalidRecipient(): self
    {
        return new self(
            'The MAX recipient must be an integer user ID or an array with user_id/chat_id.'
        );
    }

    public static function fileAccessFailed(string $file): self
    {
        return new self("Failed to open file: {$file}");
    }

    public static function invalidFile(string $file): self
    {
        return new self("Invalid file path: {$file}");
    }

    public static function missingUploadUrl(): self
    {
        return new self('MAX upload URL was not returned by the API.');
    }

    public static function missingUploadToken(string $type): self
    {
        return new self("MAX upload token was not returned for {$type} attachment.");
    }

    public static function missingSubscriptionUrl(): self
    {
        return new self('MAX webhook URL is required for subscribe/unsubscribe.');
    }

    public function isAttachmentNotReady(): bool
    {
        return str_contains(mb_strtolower($this->getMessage()), 'attachment.not.ready');
    }
}
