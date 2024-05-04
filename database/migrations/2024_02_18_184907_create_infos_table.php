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
        Schema::create('infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->text('address');
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('postalCode')->nullable();
            $table->string('document')->nullable();
            $table->string('registrationDate')->nullable();
            $table->string('registrationStatus')->nullable();
            $table->string('activityCode')->nullable();
            $table->string('bankAccount')->nullable();
            $table->string('roInvoiceStatus')->nullable();
            $table->string('authorityName')->nullable();
            $table->string('formOfOwnership')->nullable();
            $table->string('organizationalForm')->nullable();
            $table->string('legalForm')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('infos');
    }
};
