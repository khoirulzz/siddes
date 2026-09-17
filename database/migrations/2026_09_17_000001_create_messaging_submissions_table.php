<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('request_uuid')->unique();
            $table->unsignedBigInteger('actor_id')->index();
            $table->string('payload_hash', 64);
            $table->string('status', 20)->default('PENDING');
            $table->uuid('campaign_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('messaging_submissions'); }
};
