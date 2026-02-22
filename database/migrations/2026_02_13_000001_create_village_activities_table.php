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
        Schema::create('village_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->date('activity_date');
            $table->string('location');
            $table->string('person_in_charge')->nullable();
            $table->string('status')->default('Perencanaan');
            $table->decimal('budget', 16, 2)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();

            $table->index(['activity_date', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_activities');
    }
};
