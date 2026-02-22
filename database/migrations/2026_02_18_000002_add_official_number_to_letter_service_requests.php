<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_service_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('letter_service_requests', 'letter_code')) {
                $table->string('letter_code', 10)->nullable()->after('letter_type');
            }
            if (! Schema::hasColumn('letter_service_requests', 'letter_sequence')) {
                $table->unsignedInteger('letter_sequence')->nullable()->after('letter_code');
            }
            if (! Schema::hasColumn('letter_service_requests', 'official_number')) {
                $table->string('official_number', 80)->nullable()->after('letter_sequence');
            }
                $table->unique('official_number');
                $table->index(['letter_code', 'letter_sequence']);
        });
    }

    public function down(): void
    {
        Schema::table('letter_service_requests', function (Blueprint $table) {
            try {
                $table->dropUnique(['official_number']);
            } catch (\Throwable) {
            }

            try {
                $table->dropIndex(['letter_code', 'letter_sequence']);
            } catch (\Throwable) {
            }
        });

        Schema::table('letter_service_requests', function (Blueprint $table) {
            foreach (['letter_code', 'letter_sequence', 'official_number'] as $column) {
                if (Schema::hasColumn('letter_service_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
