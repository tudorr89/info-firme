<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the relation between imported ONRC records and their company intact.
 *
 * Most ONRC files are linked by the trade registry number (`reg_com`), while
 * the related tables also store a nullable `company_id` foreign key. When the
 * company file and the related files are imported in parallel, records can be
 * written before their company exists, leaving `company_id` empty. This
 * service backfills those links and can prune rows that point at companies
 * that are not imported (for example banned CIFs).
 */
class CompanyLinkService
{
    /**
     * Tables that carry both a `registration` column and a nullable
     * `company_id` foreign key.
     *
     * @var array<int, string>
     */
    public const LINKED_TABLES = [
        'legal_representatives',
        'natural_person_representatives',
        'e_u_branches',
    ];

    /**
     * Tables that are related to a company only through `registration`.
     *
     * @var array<int, string>
     */
    public const REGISTRATION_TABLES = [
        'statuses',
        'caen_companies',
    ];

    /**
     * @return array<string, int> number of rows linked, keyed by table
     */
    public function linkAll(): array
    {
        $linked = [];

        foreach (self::LINKED_TABLES as $table) {
            $linked[$table] = $this->linkTable($table);
        }

        Log::info('Company links backfilled', $linked);

        return $linked;
    }

    /**
     * @return array<string, int> number of orphan rows removed, keyed by table
     */
    public function pruneOrphans(): array
    {
        $pruned = [];

        foreach (self::LINKED_TABLES as $table) {
            $pruned[$table] = DB::delete(
                "DELETE related FROM {$table} related
                 LEFT JOIN companies company ON company.reg_com = related.registration
                 WHERE company.id IS NULL"
            );
        }

        foreach (self::REGISTRATION_TABLES as $table) {
            $pruned[$table] = DB::delete(
                "DELETE related FROM {$table} related
                 LEFT JOIN companies company ON company.reg_com = related.registration
                 WHERE company.id IS NULL"
            );
        }

        Log::info('Orphan import rows pruned', $pruned);

        return $pruned;
    }

    /**
     * @return array<string, int> number of rows still missing a link
     */
    public function unlinkedCounts(): array
    {
        $counts = [];

        foreach (self::LINKED_TABLES as $table) {
            $counts[$table] = DB::table($table)->whereNull('company_id')->count();
        }

        foreach (self::REGISTRATION_TABLES as $table) {
            $counts[$table] = DB::table($table)
                ->leftJoin('companies', 'companies.reg_com', '=', "{$table}.registration")
                ->whereNull('companies.id')
                ->count();
        }

        return $counts;
    }

    private function linkTable(string $table): int
    {
        return DB::update(
            "UPDATE {$table} related
             INNER JOIN companies company ON company.reg_com = related.registration
             SET related.company_id = company.id
             WHERE related.company_id IS NULL"
        );
    }
}
