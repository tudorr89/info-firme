<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CifRemovalStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'În așteptare',
            self::Approved => 'Aprobată',
            self::Rejected => 'Respinsă',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /**
     * Only an approved request makes the CIF disappear from the application.
     */
    public function isApproved(): bool
    {
        return $this === self::Approved;
    }
}
