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
        Schema::create('population_records', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('nik')->unique();
            $table->string('nkk');
            $table->string('birth_place');
            $table->date('birth_date');
            $table->enum('gender', ['Laki-laki', 'Perempuan']);
            $table->string('hamlet');
            $table->string('religion');
            $table->string('occupation');
            $table->text('address_detail')->nullable();
            $table->string('source_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('population_records');
    }
};
