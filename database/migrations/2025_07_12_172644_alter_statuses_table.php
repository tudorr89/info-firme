<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->index('registration'); // Ensure this index exists
            $table->index(['registration', 'status']); // Composite index if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: This migration only adds indexes which are idempotent
    }
};
