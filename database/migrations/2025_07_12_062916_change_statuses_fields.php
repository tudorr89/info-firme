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
            $table->dropForeign('statuses_company_id_foreign');
            $table->dropColumn('company_id');
            $table->string('registration')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained();
            $table->dropColumn('registration');
        });
    }
};
