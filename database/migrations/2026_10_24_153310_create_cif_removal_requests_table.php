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
        Schema::create('cif_removal_requests', function (Blueprint $table) {
            $table->id();

            // The CIF the request is about. Not a foreign key: the company row
            // is deleted when the request is approved, and a CIF may legally
            // belong to more than one company over time.
            $table->string('cui', 20)->index();
            $table->string('company_name')->nullable();

            $table->string('requester_name');
            $table->string('requester_email');
            $table->text('reason');

            $table->string('status', 20)->default('pending')->index();
            $table->string('ip_address', 45)->nullable();

            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'cui']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cif_removal_requests');
    }
};
