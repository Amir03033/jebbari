<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Editor = 'editor';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Eigenaar',
            self::Editor => 'Redacteur',
            self::Support => 'Inbox-beheer',
        };
    }
}
