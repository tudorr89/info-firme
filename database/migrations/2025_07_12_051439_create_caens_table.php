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
        Schema::create('caens', function (Blueprint $table) {
            //SECTIUNEA^SUBSECTIUNEA^DIVIZIUNEA^GRUPA^CLASA^DENUMIRE^VERSIUNE_CAEN
            $table->id();
            $table->string('section')->nullable();
            $table->string('subsection')->nullable();
            $table->integer('division')->nullable();
            $table->integer('group')->nullable();
            $table->integer('class')->nullable();
            $table->string('name')->nullable();
            $table->integer('version')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caens');
    }
};
