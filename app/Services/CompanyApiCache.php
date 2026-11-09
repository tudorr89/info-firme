<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for the name search of the public API.
 *
 * The results are not stored under a tag (the cache store may not support
 * tags), so a version stamp is used instead: bumping it makes every cached
 * page of every name unreachable. That is done when a company is deleted, so a
 * removed company cannot be served from a name search cached before the
 * deletion.
 */
final class CompanyApiCache
{
    private const NAME_VERSION_KEY = 'company_api_name_version';

    public static function nameKey(string $name, int $page): string
    {
        return sprintf('company_api_name_%s_page_%d_v%d', md5($name), $page, self::nameVersion());
    }

    public static function forgetNames(): void
    {
        Cache::forever(self::NAME_VERSION_KEY, self::nameVersion() + 1);
    }

    private static function nameVersion(): int
    {
        return (int) Cache::get(self::NAME_VERSION_KEY, 1);
    }
}
