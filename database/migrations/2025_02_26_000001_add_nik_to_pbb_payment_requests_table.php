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
        if (Schema::hasTable('pbb_payment_requests') && ! Schema::hasColumn('pbb_payment_requests', 'nik')) {
            Schema::table('pbb_payment_requests', function (Blueprint $table) {
                $table->string('nik', 16)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pbb_payment_requests') && Schema::hasColumn('pbb_payment_requests', 'nik')) {
            Schema::table('pbb_payment_requests', function (Blueprint $table) {
                $table->dropColumn('nik');
            });
        }
    }
};
