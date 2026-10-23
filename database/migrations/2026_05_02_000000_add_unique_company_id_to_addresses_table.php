<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each company has exactly one address, so `company_id` can be unique. This
 * lets the importer upsert addresses in a single statement instead of reading
 * the existing rows and updating them one by one.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::delete(
            'DELETE a1 FROM addresses a1 INNER JOIN addresses a2
             ON a1.company_id = a2.company_id AND a1.id > a2.id'
        );

        Schema::table('addresses', function (Blueprint $table) {
            $table->unique('company_id', 'addresses_company_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropUnique('addresses_company_id_unique');
        });
    }
};
