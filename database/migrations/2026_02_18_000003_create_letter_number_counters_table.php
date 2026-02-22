<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('letter_number_counters')) {
            Schema::create('letter_number_counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->string('letter_code', 10);
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();

                $table->unique(['year', 'letter_code']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_number_counters');
    }
};
