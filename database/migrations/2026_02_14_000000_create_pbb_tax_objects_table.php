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
        if (! Schema::hasTable('pbb_tax_objects')) {
            Schema::create('pbb_tax_objects', function (Blueprint $table) {
                $table->id();
                $table->string('nop')->unique()->comment('Nomor Obyek Pajak');
                $table->string('tax_name')->comment('Nama Obyek Pajak');
                $table->string('owner_name')->nullable()->comment('Nama Pemilik');
                $table->string('location')->nullable()->comment('Lokasi/Alamat Obyek');
                $table->decimal('land_area', 10, 2)->nullable()->comment('Luas Tanah');
                $table->decimal('building_area', 10, 2)->nullable()->comment('Luas Bangunan');
                $table->integer('tax_year')->comment('Tahun Pajak');
                $table->decimal('amount_due', 12, 2)->comment('Jumlah Tagihan');
                $table->string('status')->default('Aktif')->comment('Status: Aktif/Tidak Aktif');
                $table->text('notes')->nullable();
                $table->timestamps();
                
                $table->index('nop');
                $table->index('tax_year');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pbb_tax_objects');
    }
};
