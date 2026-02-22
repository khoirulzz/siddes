<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom purpose dan attachment_url sudah nullable dari migrasi awal
        // Migration ini tidak perlu melakukan apa-apa
    }

    public function down(): void
    {
        // Tidak ada yang perlu di-revert
    }
};
