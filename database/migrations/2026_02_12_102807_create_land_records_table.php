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
        Schema::create('land_records', function (Blueprint $table) {
            $table->id();
            $table->string('land_code')->nullable();
            $table->string('location');
            $table->string('hamlet')->nullable();
            $table->string('category');
            $table->decimal('area_m2', 14, 2)->default(0);
            $table->string('ownership_status')->default('Aset Desa');
            $table->string('owner_name')->nullable();
            $table->string('certificate_number')->nullable();
            $table->string('tax_object_number')->nullable();
            $table->string('status')->default('Aktif');
            $table->string('photo_path')->nullable();
            $table->string('document_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('land_records');
    }
};
