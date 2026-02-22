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
        Schema::create('letter_service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('nik', 20);
            $table->string('phone', 30);
            $table->string('letter_type');
            $table->json('dynamic_data')->nullable();
            $table->string('email')->nullable();
            $table->string('purpose')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('status')->default('Diajukan');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_service_requests');
    }
};
