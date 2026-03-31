<?php

declare(strict_types=1);

namespace NotificationChannels\Max\Ship\Enums;

enum TextFormat: string
{
    case Markdown = 'markdown';
    case Html = 'html';
}
