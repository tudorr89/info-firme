<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            $table->unique('registration', 'statuses_registration_unique');
        });

        Schema::table('caen_companies', function (Blueprint $table) {
            $table->unique(['registration', 'code'], 'caen_companies_registration_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('caen_companies', function (Blueprint $table) {
            $table->dropUnique('caen_companies_registration_code_unique');
        });

        Schema::table('statuses', function (Blueprint $table) {
            $table->dropUnique('statuses_registration_unique');
        });
    }
};
