<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_daily_stats', function (Blueprint $table) {
            $table->date('visit_date')->primary();
            $table->unsignedBigInteger('visitors')->default(0);
        });

        Schema::create('website_visitor_days', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date');
            $table->char('visitor_hash', 64);
            $table->unique(['visit_date', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_visitor_days');
        Schema::dropIfExists('website_daily_stats');
    }
};
