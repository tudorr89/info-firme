<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `euid` is unique, but not every ONRC row carries one. An empty string
     * would collide on the unique index and the import's
     * `ON DUPLICATE KEY UPDATE` would overwrite a different company. NULL rows
     * do not collide in MySQL/MariaDB, so unknown EUIDs are stored as NULL.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('euid')->nullable()->change();
        });

        DB::table('companies')->where('euid', '')->update(['euid' => null]);
    }

    /**
     * Reverting is lossy: rows without an EUID have to collide again. They are
     * mapped to a unique placeholder so the unique index can be restored.
     */
    public function down(): void
    {
        DB::table('companies')->whereNull('euid')->update([
            'euid' => DB::raw("CONCAT('MISSING-', `id`)"),
        ]);

        Schema::table('companies', function (Blueprint $table) {
            $table->string('euid')->nullable(false)->change();
        });
    }
};
