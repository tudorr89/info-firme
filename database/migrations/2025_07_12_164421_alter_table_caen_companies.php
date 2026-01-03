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
        Schema::table('caen_companies', function (Blueprint $table) {
            // Add composite index for faster lookups
            $table->index(['registration', 'code'], 'idx_registration_code');

            // Add individual indexes if needed
            $table->index('registration');
            $table->index('code');
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
