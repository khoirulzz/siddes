<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('population_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->char('file_hash', 64)->index();
            $table->string('format', 12);
            $table->string('status', 24)->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('households_created')->default(0);
            $table->unsignedInteger('residents_created')->default(0);
            $table->unsignedInteger('residents_updated')->default(0);
            $table->unsignedInteger('residents_unchanged')->default(0);
            $table->unsignedInteger('residents_moved')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('population_import_runs');
    }
};
