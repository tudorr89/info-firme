<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel Pulse was removed: its recorders ran on every request and the
 * aggregations were the largest tables in the database.
 *
 * The migration that created these tables has been deleted together with the
 * package, so `up()` only cleans up databases that were created while Pulse was
 * still installed. `down()` is intentionally empty — recreating the tables
 * would require reinstalling Pulse.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('pulse_aggregates');
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_values');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
