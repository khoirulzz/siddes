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
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature', 50);
            $table->string('provider', 50)->default('openrouter');
            $table->string('primary_model', 190);
            $table->string('fallback_model', 190)->nullable();
            $table->string('used_model', 190)->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->string('status', 20);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['feature', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};

