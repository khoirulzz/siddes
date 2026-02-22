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
        Schema::dropIfExists('finance_reports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('finance_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('program_name');
            $table->string('funding_source');
            $table->decimal('income_budget', 16, 2)->default(0);
            $table->decimal('income_realization', 16, 2)->default(0);
            $table->decimal('expenditure_budget', 16, 2)->default(0);
            $table->decimal('expenditure_realization', 16, 2)->default(0);
            $table->decimal('financing_budget', 16, 2)->default(0);
            $table->decimal('financing_realization', 16, 2)->default(0);
            $table->string('document_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
};
