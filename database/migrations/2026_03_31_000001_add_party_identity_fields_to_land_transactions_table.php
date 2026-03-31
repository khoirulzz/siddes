<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('land_transactions', function (Blueprint $table): void {
            $table->string('party_a_identifier', 160)->nullable()->after('party_a_name');
            $table->string('party_a_address', 500)->nullable()->after('party_a_page');
            $table->string('party_b_identifier', 160)->nullable()->after('party_b_name');
            $table->string('party_b_address', 500)->nullable()->after('party_b_page');
        });
    }

    public function down(): void
    {
        Schema::table('land_transactions', function (Blueprint $table): void {
            $table->dropColumn([
                'party_a_identifier',
                'party_a_address',
                'party_b_identifier',
                'party_b_address',
            ]);
        });
    }
};
