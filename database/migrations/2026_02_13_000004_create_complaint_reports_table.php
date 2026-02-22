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
        Schema::create('complaint_reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 30)->unique();
            $table->string('reporter_name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('subject');
            $table->string('category');
            $table->longText('description');
            $table->string('location')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status')->default('Diterima');
            $table->longText('response')->nullable();
            $table->string('handled_by')->nullable();
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
        Schema::dropIfExists('complaint_reports');
    }
};
