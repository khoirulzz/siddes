<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_service_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('letter_service_requests', 'applicant_name')) {
                $table->string('applicant_name')->nullable()->after('ticket_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('letter_service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('letter_service_requests', 'applicant_name')) {
                $table->dropColumn('applicant_name');
            }
        });
    }
};
