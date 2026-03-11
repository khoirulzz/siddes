<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('households')) {
            Schema::create('households', function (Blueprint $table): void {
                $table->id();
                $table->string('no_kk', 20)->unique();
                $table->string('nama_kepala_keluarga')->nullable();
                $table->text('alamat')->nullable();
                $table->string('rt', 10)->nullable();
                $table->string('rw', 10)->nullable();
                $table->string('kode_pos', 12)->nullable();
                $table->string('dusun', 120)->nullable();
                $table->string('desa', 120)->nullable();
                $table->string('kecamatan', 120)->nullable();
                $table->string('kabupaten', 120)->nullable();
                $table->string('provinsi', 120)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('household_members')) {
            Schema::create('household_members', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
                $table->foreignId('resident_id')->constrained('population_records')->cascadeOnDelete();
                $table->string('status_hubungan', 100)->nullable();
                $table->unsignedSmallInteger('no_urut_kk')->nullable();
                $table->boolean('is_kepala_keluarga')->default(false);
                $table->boolean('is_current')->default(true);
                $table->dateTime('started_at')->nullable();
                $table->dateTime('ended_at')->nullable();
                $table->timestamps();

                $table->index(['household_id', 'is_current']);
                $table->index(['resident_id', 'is_current']);
                $table->unique(['household_id', 'resident_id', 'is_current'], 'hh_member_unique_current');
            });
        }

        Schema::table('population_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('population_records', 'jenis_pekerjaan')) {
                $table->string('jenis_pekerjaan')->nullable()->after('pekerjaan');
            }
            if (! Schema::hasColumn('population_records', 'status_hubungan')) {
                $table->string('status_hubungan', 100)->nullable()->after('status_perkawinan');
            }
            if (! Schema::hasColumn('population_records', 'no_paspor')) {
                $table->string('no_paspor', 80)->nullable()->after('kewarganegaraan');
            }
            if (! Schema::hasColumn('population_records', 'no_kitas_kitap')) {
                $table->string('no_kitas_kitap', 80)->nullable()->after('no_paspor');
            }
            if (! Schema::hasColumn('population_records', 'nama_ayah')) {
                $table->string('nama_ayah')->nullable()->after('no_kitas_kitap');
            }
            if (! Schema::hasColumn('population_records', 'nama_ibu')) {
                $table->string('nama_ibu')->nullable()->after('nama_ayah');
            }
            if (! Schema::hasColumn('population_records', 'golongan_darah')) {
                $table->string('golongan_darah', 5)->nullable()->after('nama_ibu');
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('household_members')->truncate();
        DB::table('households')->truncate();
        DB::table('population_records')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $now = now();

        $householdId = DB::table('households')->insertGetId([
            'no_kk' => '3326010101010001',
            'nama_kepala_keluarga' => 'Budi Santoso',
            'alamat' => 'RT 001 RW 002 Dusun Bojongireng',
            'rt' => '001',
            'rw' => '002',
            'kode_pos' => '51164',
            'dusun' => 'Bojongireng',
            'desa' => 'Desa Lambanggelun',
            'kecamatan' => 'Kecamatan Paninggaran',
            'kabupaten' => 'Kabupaten Pekalongan',
            'provinsi' => 'Provinsi Jawa Tengah',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $dummyResidents = [
            [
                'nama_lengkap' => 'Budi Santoso',
                'nik' => '3326010101800001',
                'jenis_kelamin' => 'Laki-laki',
                'tempat_lahir' => 'Pekalongan',
                'tanggal_lahir' => '1980-01-01',
                'agama' => 'Islam',
                'pendidikan' => 'SMA/Sederajat',
                'pekerjaan' => 'Petani',
                'jenis_pekerjaan' => 'Petani',
                'status_perkawinan' => 'Kawin Tercatat',
                'status_hubungan' => 'Kepala Keluarga',
                'kewarganegaraan' => 'WNI',
                'nama_ayah' => 'Sutrisno',
                'nama_ibu' => 'Suminah',
                'golongan_darah' => 'O',
                'no_urut_kk' => 1,
                'is_kepala' => true,
            ],
            [
                'nama_lengkap' => 'Siti Aminah',
                'nik' => '3326014502850002',
                'jenis_kelamin' => 'Perempuan',
                'tempat_lahir' => 'Pekalongan',
                'tanggal_lahir' => '1985-02-05',
                'agama' => 'Islam',
                'pendidikan' => 'SMA/Sederajat',
                'pekerjaan' => 'Ibu Rumah Tangga',
                'jenis_pekerjaan' => 'Ibu Rumah Tangga',
                'status_perkawinan' => 'Kawin Tercatat',
                'status_hubungan' => 'Istri',
                'kewarganegaraan' => 'WNI',
                'nama_ayah' => 'Kasim',
                'nama_ibu' => 'Sukarti',
                'golongan_darah' => 'A',
                'no_urut_kk' => 2,
                'is_kepala' => false,
            ],
            [
                'nama_lengkap' => 'Rina Lestari',
                'nik' => '3326014706100003',
                'jenis_kelamin' => 'Perempuan',
                'tempat_lahir' => 'Pekalongan',
                'tanggal_lahir' => '2010-06-07',
                'agama' => 'Islam',
                'pendidikan' => 'Pelajar',
                'pekerjaan' => 'Pelajar/Mahasiswa',
                'jenis_pekerjaan' => 'Pelajar/Mahasiswa',
                'status_perkawinan' => 'Belum Kawin',
                'status_hubungan' => 'Anak',
                'kewarganegaraan' => 'WNI',
                'nama_ayah' => 'Budi Santoso',
                'nama_ibu' => 'Siti Aminah',
                'golongan_darah' => 'B',
                'no_urut_kk' => 3,
                'is_kepala' => false,
            ],
        ];

        foreach ($dummyResidents as $resident) {
            $residentId = DB::table('population_records')->insertGetId([
                'full_name' => $resident['nama_lengkap'],
                'nama_lengkap' => $resident['nama_lengkap'],
                'nik' => $resident['nik'],
                'nkk' => '3326010101010001',
                'no_kk' => '3326010101010001',
                'birth_place' => $resident['tempat_lahir'],
                'tempat_lahir' => $resident['tempat_lahir'],
                'birth_date' => $resident['tanggal_lahir'],
                'tanggal_lahir' => $resident['tanggal_lahir'],
                'gender' => $resident['jenis_kelamin'],
                'jenis_kelamin' => $resident['jenis_kelamin'],
                'hamlet' => 'Bojongireng',
                'dusun' => 'Bojongireng',
                'religion' => $resident['agama'],
                'agama' => $resident['agama'],
                'occupation' => $resident['pekerjaan'],
                'pekerjaan' => $resident['pekerjaan'],
                'jenis_pekerjaan' => $resident['jenis_pekerjaan'],
                'pendidikan' => $resident['pendidikan'],
                'status_perkawinan' => $resident['status_perkawinan'],
                'status_hubungan' => $resident['status_hubungan'],
                'kewarganegaraan' => $resident['kewarganegaraan'],
                'no_paspor' => null,
                'no_kitas_kitap' => null,
                'nama_ayah' => $resident['nama_ayah'],
                'nama_ibu' => $resident['nama_ibu'],
                'golongan_darah' => $resident['golongan_darah'],
                'rt' => '001',
                'rw' => '002',
                'desa' => 'Desa Lambanggelun',
                'kecamatan' => 'Kecamatan Paninggaran',
                'kabupaten' => 'Kabupaten Pekalongan',
                'provinsi' => 'Provinsi Jawa Tengah',
                'kode_pos' => '51164',
                'address_detail' => 'RT 001 RW 002 Dusun Bojongireng',
                'source_file' => 'dummy-seed-v2',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('household_members')->insert([
                'household_id' => $householdId,
                'resident_id' => $residentId,
                'status_hubungan' => $resident['status_hubungan'],
                'no_urut_kk' => $resident['no_urut_kk'],
                'is_kepala_keluarga' => $resident['is_kepala'],
                'is_current' => true,
                'started_at' => $now,
                'ended_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('household_members')) {
            Schema::drop('household_members');
        }

        if (Schema::hasTable('households')) {
            Schema::drop('households');
        }

        Schema::table('population_records', function (Blueprint $table): void {
            foreach ([
                'jenis_pekerjaan',
                'status_hubungan',
                'no_paspor',
                'no_kitas_kitap',
                'nama_ayah',
                'nama_ibu',
                'golongan_darah',
            ] as $column) {
                if (Schema::hasColumn('population_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

