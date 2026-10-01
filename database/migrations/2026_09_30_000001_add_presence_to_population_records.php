<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('population_records', function (Blueprint $table): void {
            $table->string('pendidikan_update')->nullable()->after('pendidikan');
            $table->string('status_keberadaan', 20)->default('ditemukan')->index()->after('pendidikan_update');
        });

        Schema::create('population_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('population_record_id')->constrained('population_records')->cascadeOnDelete();
            $table->string('jenis', 30);
            $table->string('status_keberadaan', 20);
            $table->string('original_name');
            $table->unsignedBigInteger('size');
            $table->string('cloudinary_asset_id')->unique();
            $table->string('cloudinary_public_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('population_documents');
        Schema::table('population_records', function (Blueprint $table): void {
            $table->dropIndex(['status_keberadaan']);
            $table->dropColumn(['pendidikan_update', 'status_keberadaan']);
        });
    }
};
