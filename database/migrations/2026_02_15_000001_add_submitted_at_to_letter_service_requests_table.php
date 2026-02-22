<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_service_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('letter_service_requests', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('admin_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('letter_service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('letter_service_requests', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });
    }
};
