<?php

namespace App\Services;

use App\Models\CifRemovalRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * The single source of truth for the CIFs that must never be published.
 *
 * Two layers:
 *  - `config('skip.always')`, enforced in code, so a missing row, a bad
 *    deployment or an empty table cannot bring a banned company back;
 *  - approved entries in `cif_removal_requests`, managed from the admin panel
 *    (this replaced the old SKIP_CIFS_IMPORT environment variable).
 */
final class BannedCifService
{
    private const CACHE_KEY = 'banned_cifs';

    /**
     * Determine whether a CIF/CUI is banned from import and from publication.
     */
    public static function isBanned(string $cui): bool
    {
        return in_array(trim($cui), self::all(), true);
    }

    /**
     * Exclude the banned CIFs from a companies query.
     *
     * The import already skips them, but a row imported before the ban was
     * enforced in code — or restored from a production dump — still exists in
     * the database and must never be published.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function exclude(Builder $query): Builder
    {
        $banned = self::all();

        if ($banned === []) {
            return $query;
        }

        return $query->whereNotIn('companies.cui', $banned);
    }

    /**
     * Every CIF that must never be published: the ones enforced in code plus
     * the approved removal requests.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(10),
            fn (): array => self::resolve()
        );
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, string>
     */
    private static function resolve(): array
    {
        $always = array_map('trim', (array) config('skip.always', []));

        $approved = CifRemovalRequest::query()
            ->approved()
            ->pluck('cui')
            ->map(fn (string $cui): string => trim($cui))
            ->all();

        return array_values(array_unique(array_filter(array_merge($always, $approved))));
    }
}
