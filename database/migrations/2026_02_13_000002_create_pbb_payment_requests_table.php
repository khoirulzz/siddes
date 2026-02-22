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
        Schema::create('pbb_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_name');
            $table->string('nik', 20);
            $table->string('nop', 40);
            $table->unsignedSmallInteger('tax_year');
            $table->decimal('amount_due', 16, 2)->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('Diajukan');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['tax_year', 'nop']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbb_payment_requests');
    }
};
