<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\DB;

/**
 * Builds large, single-statement upserts for the ONRC import.
 *
 * The importer used to read the existing rows of a batch and then issue one
 * UPDATE per company (and per address), which meant thousands of round trips
 * per batch. Everything is now done with one `INSERT ... SELECT ... ON
 * DUPLICATE KEY UPDATE` statement per chunk of rows, which MySQL/MariaDB
 * executes in a single pass over the indexes.
 *
 * Two variants are supported:
 *
 * - {@see BulkUpsert::upsert()} for tables that already carry the link key.
 * - {@see BulkUpsert::upsertForCompanies()} which joins `companies` on
 *   `reg_com`, so related rows are written only when their company exists
 *   (banned CIFs never produce orphans) and `company_id` is resolved by the
 *   database instead of a second SELECT.
 */
final class BulkUpsert
{
    /**
     * MySQL rejects prepared statements with more than 65535 placeholders, so
     * rows are chunked accordingly.
     */
    private const MAX_PLACEHOLDERS = 60000;

    /**
     * How the source rows are joined against `companies`.
     */
    private const LINK_NONE = 'none';

    private const LINK_FILTER = 'filter';

    private const LINK_RESOLVE = 'resolve';

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $updateColumns  columns overwritten on duplicate key
     * @param  array<int, string>  $compareColumns  columns checked to decide whether `updated_at` moves
     * @return int number of affected rows
     */
    public static function upsert(string $table, array $rows, array $updateColumns, array $compareColumns = []): int
    {
        $affected = 0;

        foreach (self::chunks($rows) as $chunk) {
            $affected += DB::affectingStatement(
                self::statement($table, $chunk, $updateColumns, $compareColumns, null, self::LINK_NONE),
                self::bindings($chunk)
            );
        }

        return $affected;
    }

    /**
     * Upsert related rows, resolving `company_id` by joining `companies` on the
     * `registration` column of the source rows.
     *
     * @param  array<int, array<string, mixed>>  $rows  every row must contain the join column
     * @param  array<int, string>  $updateColumns
     * @param  array<int, string>  $compareColumns
     * @return int number of affected rows
     */
    public static function upsertForCompanies(
        string $table,
        array $rows,
        array $updateColumns,
        array $compareColumns = [],
        string $joinColumn = 'registration'
    ): int {
        $affected = 0;

        foreach (self::chunks($rows) as $chunk) {
            $affected += DB::affectingStatement(
                self::statement($table, $chunk, $updateColumns, $compareColumns, $joinColumn, self::LINK_RESOLVE),
                self::bindings($chunk)
            );
        }

        return $affected;
    }

    /**
     * Upsert rows only when the related company exists, keeping the source
     * columns untouched (used by tables that are linked through `registration`
     * instead of holding a `company_id`).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $updateColumns
     * @param  array<int, string>  $compareColumns
     */
    public static function upsertWhereCompanyExists(
        string $table,
        array $rows,
        array $updateColumns,
        array $compareColumns = [],
        string $joinColumn = 'registration'
    ): int {
        $affected = 0;

        foreach (self::chunks($rows) as $chunk) {
            $affected += DB::affectingStatement(
                self::statement($table, $chunk, $updateColumns, $compareColumns, $joinColumn, self::LINK_FILTER),
                self::bindings($chunk)
            );
        }

        return $affected;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<int, array<string, mixed>>>
     */
    private static function chunks(array $rows): array
    {
        $rows = array_values($rows);

        if ($rows === []) {
            return [];
        }

        $columns = max(count($rows[0]), 1);
        $size = max((int) floor(self::MAX_PLACEHOLDERS / $columns), 1);

        return array_chunk($rows, $size);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $updateColumns
     * @param  array<int, string>  $compareColumns
     */
    private static function statement(
        string $table,
        array $rows,
        array $updateColumns,
        array $compareColumns,
        ?string $joinColumn,
        string $linkMode
    ): string {
        $columns = array_keys($rows[0]);

        $rowSelect = 'SELECT '.implode(', ', array_map(
            fn (string $column) => "? AS `{$column}`",
            $columns
        ));

        $source = '('.implode(' UNION ALL ', array_fill(0, count($rows), $rowSelect)).') AS src';

        $targetColumns = $columns;
        $selectColumns = array_map(fn (string $column) => "src.`{$column}`", $columns);

        if ($joinColumn !== null) {
            if ($linkMode === self::LINK_RESOLVE) {
                $targetColumns = array_map(
                    fn (string $column) => $column === $joinColumn ? 'company_id' : $column,
                    $columns
                );

                $selectColumns = array_map(
                    fn (string $column) => $column === $joinColumn ? 'company.`id`' : "src.`{$column}`",
                    $columns
                );
            }

            $source .= " INNER JOIN `companies` AS company ON company.`reg_com` = src.`{$joinColumn}`";
        }

        return sprintf(
            'INSERT INTO `%s` (%s) SELECT %s FROM %s ON DUPLICATE KEY UPDATE %s',
            $table,
            self::quote($targetColumns),
            implode(', ', $selectColumns),
            $source,
            self::updateClause($table, $updateColumns, $compareColumns)
        );
    }

    /**
     * @param  array<int, string>  $columns
     */
    private static function quote(array $columns): string
    {
        return implode(', ', array_map(fn (string $column) => "`{$column}`", $columns));
    }

    /**
     * Only `updated_at` is moved forward when a compared column actually
     * changed, otherwise a monthly re-import would mark every row as updated
     * (and regenerate the whole sitemap).
     *
     * Columns are qualified with the target table because the `SELECT` source
     * exposes the same names, which MySQL would otherwise report as ambiguous.
     *
     * @param  array<int, string>  $updateColumns
     * @param  array<int, string>  $compareColumns
     */
    private static function updateClause(string $table, array $updateColumns, array $compareColumns): string
    {
        $update = array_map(
            fn (string $column) => "`{$table}`.`{$column}` = VALUES(`{$column}`)",
            $updateColumns
        );

        if ($compareColumns === []) {
            return implode(', ', $update);
        }

        $differs = implode(' OR ', array_map(
            fn (string $column) => "NOT (`{$table}`.`{$column}` <=> VALUES(`{$column}`))",
            $compareColumns
        ));

        // `updated_at` must be assigned before the compared columns: MySQL and
        // MariaDB evaluate the assignments from left to right, so a later
        // comparison would already see the new values and never report a change.
        return implode(', ', [
            "`{$table}`.`updated_at` = IF({$differs}, VALUES(`updated_at`), `{$table}`.`updated_at`)",
            ...$update,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, mixed>
     */
    private static function bindings(array $rows): array
    {
        $bindings = [];

        foreach ($rows as $row) {
            foreach ($row as $value) {
                $bindings[] = $value;
            }
        }

        return $bindings;
    }
}
