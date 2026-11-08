<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nieuw',
            self::InProgress => 'In behandeling',
            self::Completed => 'Afgehandeld',
            self::Spam => 'Spam',
        };
    }
}
