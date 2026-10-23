<?php

namespace App\Services;

use App\Models\Company;

/**
 * Interprets the ONRC company status codes.
 *
 * `od_stare_firma.csv` links every company to a code from `n_stare_firma.csv`.
 * Only 1048 (`funcțiune`) means the company is currently operating; the rest
 * describe dissolution, liquidation, insolvency, expired registered office and
 * similar situations.
 */
final class CompanyStatusService
{
    public const ACTIVE = 1048;

    /**
     * @return array<int, int>
     */
    public static function activeCodes(): array
    {
        return (array) config('companies.active_statuses', [self::ACTIVE]);
    }

    /**
     * @return array<int, int>
     */
    public static function obsoleteCodes(): array
    {
        return (array) config('companies.obsolete_statuses', []);
    }

    public static function isActive(?Company $company): bool
    {
        return in_array((int) $company?->status?->status, self::activeCodes(), true);
    }

    public static function isObsolete(?Company $company): bool
    {
        return in_array((int) $company?->status?->status, self::obsoleteCodes(), true);
    }

    public static function hidesObsolete(): bool
    {
        return (bool) config('companies.hide_obsolete', true);
    }
}
