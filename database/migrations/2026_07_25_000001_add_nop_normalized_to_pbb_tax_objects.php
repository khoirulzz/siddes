<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pbb_tax_objects', function (Blueprint $table) {
            $table->string('nop_normalized', 40)->nullable()->after('nop');
        });

        // Update existing data
        DB::statement("UPDATE pbb_tax_objects SET nop_normalized = REPLACE(REPLACE(REPLACE(REPLACE(nop, '.', ''), '-', ''), ' ', ''), '/', '')");

        Schema::table('pbb_tax_objects', function (Blueprint $table) {
            $table->index('nop_normalized');
            $table->index('tax_year');
        });
    }

    public function down(): void
    {
        Schema::table('pbb_tax_objects', function (Blueprint $table) {
            $table->dropIndex(['nop_normalized']);
            $table->dropIndex(['tax_year']);
            $table->dropColumn('nop_normalized');
        });
    }
};
