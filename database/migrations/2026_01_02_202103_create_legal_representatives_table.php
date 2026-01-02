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
        Schema::create('legal_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('registration')->index();
            $table->string('person_name')->nullable();
            $table->string('role')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_location')->nullable();
            $table->string('birth_county')->nullable();
            $table->string('birth_country')->nullable();
            $table->string('current_location')->nullable();
            $table->string('current_county')->nullable();
            $table->string('current_country')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_representatives');
    }
};
