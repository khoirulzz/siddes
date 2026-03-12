<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('land_transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('transaction_number', 40)->unique();
            $table->date('transaction_date');
            $table->string('transaction_type', 30);
            $table->string('party_a_name', 160);
            $table->string('party_a_page', 50);
            $table->string('party_b_name', 160);
            $table->string('party_b_page', 50);
            $table->text('land_object');
            $table->decimal('area_m2', 14, 2)->nullable();
            $table->string('document_number', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['transaction_date']);
            $table->index(['transaction_type']);
            $table->index(['party_a_page']);
            $table->index(['party_b_page']);
            $table->index(['party_a_name']);
            $table->index(['party_b_name']);
        });

        Schema::create('land_transaction_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('land_transaction_id')->constrained('land_transactions')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['mime_type']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('land_transaction_files');
        Schema::dropIfExists('land_transactions');
    }
};

