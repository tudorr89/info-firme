<?php

namespace App\Services;

final class BannedCifService
{
    /**
     * Determine whether a CIF/CUI is banned from import.
     */
    public static function isBanned(string $cui): bool
    {
        return in_array(trim($cui), self::all(), true);
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return config('skip.cifs');
    }
}
