<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pbb_payment_requests')) {
            Schema::table('pbb_payment_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('pbb_payment_requests', 'requested_nops')) {
                    $table->json('requested_nops')->nullable()->after('nop');
                }

                if (! Schema::hasColumn('pbb_payment_requests', 'ticket_code')) {
                    $table->string('ticket_code', 30)->nullable()->after('id')->unique();
                }
            });
        }

        if (Schema::hasTable('complaint_reports')) {
            Schema::table('complaint_reports', function (Blueprint $table) {
                if (! Schema::hasColumn('complaint_reports', 'nik')) {
                    $table->string('nik', 16)->nullable()->after('ticket_code');
                    $table->index('nik');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pbb_payment_requests')) {
            Schema::table('pbb_payment_requests', function (Blueprint $table) {
                if (Schema::hasColumn('pbb_payment_requests', 'ticket_code')) {
                    $table->dropColumn('ticket_code');
                }

                if (Schema::hasColumn('pbb_payment_requests', 'requested_nops')) {
                    $table->dropColumn('requested_nops');
                }
            });
        }

        if (Schema::hasTable('complaint_reports')) {
            Schema::table('complaint_reports', function (Blueprint $table) {
                if (Schema::hasColumn('complaint_reports', 'nik')) {
                    $table->dropColumn('nik');
                }
            });
        }
    }
};
