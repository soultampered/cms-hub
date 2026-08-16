<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConnectionKind: string implements HasLabel
{
    case Database = 'db';
    case Cdn = 'cdn';

    public function getLabel(): string
    {
        return match ($this) {
            self::Database => 'Database',
            self::Cdn => 'CDN / storage',
        };
    }
}
