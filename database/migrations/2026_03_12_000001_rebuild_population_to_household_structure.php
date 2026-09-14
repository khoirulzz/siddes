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
                $table->index(['household_id', 'resident_id']);
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

        $this->backfillExistingResidents();
    }

    private function backfillExistingResidents(): void
    {
        DB::table('population_records')
            ->orderBy('id')
            ->chunkById(500, function ($residents): void {
                foreach ($residents as $resident) {
                    $noKk = preg_replace('/\D+/', '', (string) ($resident->no_kk ?: $resident->nkk));
                    if ($noKk === '') {
                        continue;
                    }

                    $household = DB::table('households')->where('no_kk', $noKk)->first();
                    if (! $household) {
                        $householdId = DB::table('households')->insertGetId([
                            'no_kk' => $noKk,
                            'nama_kepala_keluarga' => $resident->status_hubungan === 'Kepala Keluarga'
                                ? ($resident->nama_lengkap ?: $resident->full_name)
                                : null,
                            'alamat' => $resident->address_detail,
                            'rt' => $resident->rt,
                            'rw' => $resident->rw,
                            'kode_pos' => $resident->kode_pos,
                            'dusun' => $resident->dusun ?: $resident->hamlet,
                            'desa' => $resident->desa,
                            'kecamatan' => $resident->kecamatan,
                            'kabupaten' => $resident->kabupaten,
                            'provinsi' => $resident->provinsi,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $householdId = $household->id;
                    }

                    $membershipExists = DB::table('household_members')
                        ->where('resident_id', $resident->id)
                        ->where('is_current', true)
                        ->exists();
                    if ($membershipExists) {
                        continue;
                    }

                    $status = $resident->status_hubungan ?: 'Lainnya';
                    DB::table('household_members')->insert([
                        'household_id' => $householdId,
                        'resident_id' => $resident->id,
                        'status_hubungan' => $status,
                        'no_urut_kk' => null,
                        'is_kepala_keluarga' => $status === 'Kepala Keluarga',
                        'is_current' => true,
                        'started_at' => now(),
                        'ended_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($status === 'Kepala Keluarga') {
                        DB::table('households')->where('id', $householdId)->update([
                            'nama_kepala_keluarga' => $resident->nama_lengkap ?: $resident->full_name,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_members');
        Schema::dropIfExists('households');

        Schema::table('population_records', function (Blueprint $table): void {
            foreach ([
                'jenis_pekerjaan', 'status_hubungan', 'no_paspor', 'no_kitas_kitap',
                'nama_ayah', 'nama_ibu', 'golongan_darah',
            ] as $column) {
                if (Schema::hasColumn('population_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
