<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Master Data PBB (Untuk pencarian NOP)
        if (! Schema::hasTable('pbb_tax_objects')) {
            Schema::create('pbb_tax_objects', function (Blueprint $table) {
                $table->id();
                $table->string('nop')->index(); // Nomor Objek Pajak
                $table->string('tax_name'); // Nama Wajib Pajak
                $table->text('tax_address'); // Alamat Objek Pajak
                $table->year('tax_year'); // Tahun Pajak
                $table->decimal('amount_due', 12, 2); // Jumlah Tagihan
                $table->enum('status', ['Belum Lunas', 'Lunas'])->default('Belum Lunas');
                $table->timestamps();
            });
        }

        // 2. Update Tabel Request PBB (Agar bisa menampung banyak NOP dalam 1 request)
        if (Schema::hasTable('pbb_payment_requests')) {
            Schema::table('pbb_payment_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('pbb_payment_requests', 'requested_nops')) {
                    $table->json('requested_nops')->nullable()->after('nop'); // Menyimpan detail NOP yang diajukan (JSON)
                }

                if (Schema::hasColumn('pbb_payment_requests', 'nop')) {
                    $table->string('nop')->nullable()->change(); // NOP single jadi nullable karena pakai JSON
                }

                if (Schema::hasColumn('pbb_payment_requests', 'amount_due')) {
                    $table->decimal('amount_due', 12, 2)->default(0)->change(); // Total bayar
                }
            });
        }

        // 3. Update Tabel Surat (Untuk Tiket & Data Dinamis)
        if (Schema::hasTable('letter_service_requests')) {
            Schema::table('letter_service_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('letter_service_requests', 'ticket_number')) {
                    $table->string('ticket_number')->unique()->nullable()->after('id'); // Kode Tiket Unik
                }

                if (! Schema::hasColumn('letter_service_requests', 'dynamic_data')) {
                    $table->json('dynamic_data')->nullable()->after('purpose'); // Data tambahan (nama ortu, status, dll)
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pbb_tax_objects')) {
            Schema::drop('pbb_tax_objects');
        }

        if (Schema::hasTable('pbb_payment_requests') && Schema::hasColumn('pbb_payment_requests', 'requested_nops')) {
            Schema::table('pbb_payment_requests', function (Blueprint $table) {
                $table->dropColumn('requested_nops');
            });
        }

        if (Schema::hasTable('letter_service_requests')) {
            Schema::table('letter_service_requests', function (Blueprint $table) {
                $dropColumns = [];
                if (Schema::hasColumn('letter_service_requests', 'ticket_number')) {
                    $dropColumns[] = 'ticket_number';
                }
                if (Schema::hasColumn('letter_service_requests', 'dynamic_data')) {
                    $dropColumns[] = 'dynamic_data';
                }

                if ($dropColumns !== []) {
                    $table->dropColumn($dropColumns);
                }
            });
        }
    }
};
