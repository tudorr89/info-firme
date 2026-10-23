<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Removes duplicate rows created by repeated (idempotent-unaware) imports so
 * that the unique indexes added in the next migration can be created safely.
 *
 * Earlier imports inserted without any uniqueness check, so `statuses` and
 * `caen_companies` can hold the same registration (or registration + code) more
 * than once. The duplicates are deleted in bounded id windows so the migration
 * never runs one enormous transaction against a production-sized table.
 */
return new class extends Migration
{
    private const WINDOW = 500000;

    public function up(): void
    {
        $statuses = $this->removeDuplicates('statuses', ['registration']);
        $caenRows = $this->removeDuplicates('caen_companies', ['registration', 'code']);
        $addresses = $this->removeDuplicates('addresses', ['company_id']);

        Log::info('Removed duplicate import rows', [
            'statuses' => $statuses,
            'caen_companies' => $caenRows,
            'addresses' => $addresses,
        ]);
    }

    public function down(): void
    {
        // Data cleanup is not reversible.
    }

    /**
     * @param  array<int, string>  $keyColumns
     */
    private function removeDuplicates(string $table, array $keyColumns): int
    {
        $maxId = (int) DB::table($table)->max('id');

        if ($maxId === 0) {
            return 0;
        }

        $condition = implode(' AND ', array_map(
            fn (string $column) => "duplicate.`{$column}` = keeper.`{$column}`",
            $keyColumns
        ));

        $removed = 0;

        for ($start = 0; $start <= $maxId; $start += self::WINDOW) {
            $removed += DB::delete(
                "DELETE duplicate FROM `{$table}` duplicate
                 INNER JOIN `{$table}` keeper ON {$condition} AND duplicate.`id` > keeper.`id`
                 WHERE duplicate.`id` BETWEEN ? AND ?",
                [$start, $start + self::WINDOW - 1]
            );
        }

        return $removed;
    }
};
