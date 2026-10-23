<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Removes duplicate rows created by repeated (idempotent-unaware) imports so
 * that the unique indexes added in the next migration can be created safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        $statusDuplicates = DB::delete(
            'DELETE s1 FROM statuses s1 INNER JOIN statuses s2
             ON s1.registration = s2.registration AND s1.id > s2.id'
        );

        $caenDuplicates = DB::delete(
            'DELETE c1 FROM caen_companies c1 INNER JOIN caen_companies c2
             ON c1.registration = c2.registration AND c1.code = c2.code AND c1.id > c2.id'
        );

        Log::info('Removed duplicate import rows', [
            'statuses' => $statusDuplicates,
            'caen_companies' => $caenDuplicates,
        ]);
    }

    public function down(): void
    {
        // Data cleanup is not reversible.
    }
};
