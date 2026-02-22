<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('population_records', function (Blueprint $table) {
            if (! Schema::hasColumn('population_records', 'nama_lengkap')) {
                $table->string('nama_lengkap')->nullable()->after('full_name');
            }
            if (! Schema::hasColumn('population_records', 'no_kk')) {
                $table->string('no_kk', 20)->nullable()->after('nkk');
            }
            if (! Schema::hasColumn('population_records', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 30)->nullable()->after('gender');
            }
            if (! Schema::hasColumn('population_records', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable()->after('birth_place');
            }
            if (! Schema::hasColumn('population_records', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('population_records', 'agama')) {
                $table->string('agama', 100)->nullable()->after('religion');
            }
            if (! Schema::hasColumn('population_records', 'pekerjaan')) {
                $table->string('pekerjaan')->nullable()->after('occupation');
            }
            if (! Schema::hasColumn('population_records', 'pendidikan')) {
                $table->string('pendidikan')->nullable()->after('agama');
            }
            if (! Schema::hasColumn('population_records', 'status_perkawinan')) {
                $table->string('status_perkawinan', 100)->nullable()->after('pekerjaan');
            }
            if (! Schema::hasColumn('population_records', 'kewarganegaraan')) {
                $table->string('kewarganegaraan', 100)->nullable()->after('status_perkawinan');
            }
            if (! Schema::hasColumn('population_records', 'rt')) {
                $table->string('rt', 10)->nullable()->after('kewarganegaraan');
            }
            if (! Schema::hasColumn('population_records', 'rw')) {
                $table->string('rw', 10)->nullable()->after('rt');
            }
            if (! Schema::hasColumn('population_records', 'dusun')) {
                $table->string('dusun', 120)->nullable()->after('hamlet');
            }
            if (! Schema::hasColumn('population_records', 'desa')) {
                $table->string('desa', 120)->nullable()->after('dusun');
            }
            if (! Schema::hasColumn('population_records', 'kecamatan')) {
                $table->string('kecamatan', 120)->nullable()->after('desa');
            }
            if (! Schema::hasColumn('population_records', 'kabupaten')) {
                $table->string('kabupaten', 120)->nullable()->after('kecamatan');
            }
            if (! Schema::hasColumn('population_records', 'provinsi')) {
                $table->string('provinsi', 120)->nullable()->after('kabupaten');
            }
            if (! Schema::hasColumn('population_records', 'kode_pos')) {
                $table->string('kode_pos', 12)->nullable()->after('provinsi');
            }
        });

        DB::statement(<<<'SQL'
            UPDATE population_records
            SET
                nama_lengkap = COALESCE(NULLIF(nama_lengkap, ''), full_name),
                no_kk = COALESCE(NULLIF(no_kk, ''), nkk),
                jenis_kelamin = COALESCE(NULLIF(jenis_kelamin, ''), gender),
                tempat_lahir = COALESCE(NULLIF(tempat_lahir, ''), birth_place),
                tanggal_lahir = COALESCE(tanggal_lahir, birth_date),
                agama = COALESCE(NULLIF(agama, ''), religion),
                pekerjaan = COALESCE(NULLIF(pekerjaan, ''), occupation),
                kewarganegaraan = COALESCE(NULLIF(kewarganegaraan, ''), 'WNI'),
                dusun = COALESCE(NULLIF(dusun, ''), hamlet),
                desa = COALESCE(NULLIF(desa, ''), 'Desa Lambanggelun'),
                kecamatan = COALESCE(NULLIF(kecamatan, ''), 'Kecamatan Paninggaran'),
                kabupaten = COALESCE(NULLIF(kabupaten, ''), 'Kabupaten Pekalongan'),
                provinsi = COALESCE(NULLIF(provinsi, ''), 'Provinsi Jawa Tengah'),
                kode_pos = COALESCE(NULLIF(kode_pos, ''), '51164')
        SQL);
    }

    public function down(): void
    {
        Schema::table('population_records', function (Blueprint $table) {
            $columns = [
                'nama_lengkap',
                'no_kk',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'agama',
                'pekerjaan',
                'pendidikan',
                'status_perkawinan',
                'kewarganegaraan',
                'rt',
                'rw',
                'dusun',
                'desa',
                'kecamatan',
                'kabupaten',
                'provinsi',
                'kode_pos',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('population_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
