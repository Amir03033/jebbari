<?php

namespace App\Enums;

enum PreviewStatus: string
{
    case Pending = 'pending';
    case Building = 'building';
    case Ready = 'ready';
    case Failed = 'failed';
    case Cleared = 'cleared';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In de wachtrij',
            self::Building => 'Wordt gebouwd',
            self::Ready => 'Klaar',
            self::Failed => 'Mislukt',
            self::Cleared => 'Gewist',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::Building => 'info',
            self::Ready => 'success',
            self::Failed => 'danger',
            self::Cleared => 'gray',
        };
    }

    public function isBusy(): bool
    {
        return $this === self::Pending || $this === self::Building;
    }
}
