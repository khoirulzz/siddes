<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\PopulationRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PopulationImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_valid_rows_can_be_previewed_and_committed_without_deleting_other_data(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $unrelated = $this->createResident('3326010101800099', '3326010101010099');
        $contents = $this->validCsv(5);

        $preview = $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($contents),
        ]);

        $preview->assertOk()
            ->assertJsonPath('preview.summary.total', 5)
            ->assertJsonPath('preview.summary.valid', 5)
            ->assertJsonPath('preview.summary.invalid', 0)
            ->assertJsonPath('preview.summary.households_created', 1);

        $commit = $this->actingAs($user)->postJson(route('dashboard.population-records.import'), [
            'file' => $this->upload($contents),
            'preview_token' => $preview->json('token'),
        ]);

        $commit->assertOk()
            ->assertJsonPath('result.residents_created', 5)
            ->assertJsonPath('result.skipped', 0);

        $this->assertDatabaseCount('population_records', 6);
        $this->assertDatabaseCount('households', 2);
        $this->assertDatabaseHas('population_records', ['id' => $unrelated->id]);
        $this->assertDatabaseCount('population_import_runs', 1);
    }

    public function test_preview_reports_specific_errors_and_commit_imports_only_valid_rows(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $contents = $this->validCsv(2);
        $contents .= "3326010101010001;Budi Santoso;Alamat;001;002;51164;Bojongireng;Desa Lambanggelun;Kecamatan Paninggaran;Kabupaten Pekalongan;Provinsi Jawa Tengah;3;Anak;3.326010101800003E+15;Rusak;Perempuan;Pekalongan;01/01/2010;Islam;SMA;Pelajar;Belum Kawin;WNI;;;;;O\n";

        $preview = $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($contents),
        ]);

        $preview->assertOk()
            ->assertJsonPath('preview.summary.valid', 2)
            ->assertJsonPath('preview.summary.invalid', 1)
            ->assertJsonPath('preview.rows.2.issues.0.code', 'scientific_identifier');

        $this->actingAs($user)->postJson(route('dashboard.population-records.import'), [
            'file' => $this->upload($contents),
            'preview_token' => $preview->json('token'),
        ])->assertOk()->assertJsonPath('result.skipped', 1);

        $this->assertDatabaseCount('population_records', 2);
    }

    public function test_reimport_keeps_existing_values_when_cells_are_blank(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $resident = $this->createResident('3326010101800001', '3326010101010001', 'S1');
        $contents = "no_kk;nik;nama_lengkap;pendidikan\n3326010101010001;3326010101800001;;\n";

        $preview = $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($contents),
        ])->assertOk()
            ->assertJsonPath('preview.rows.0.action', 'unchanged');

        $this->actingAs($user)->postJson(route('dashboard.population-records.import'), [
            'file' => $this->upload($contents),
            'preview_token' => $preview->json('token'),
        ])->assertOk()
            ->assertJsonPath('result.residents_unchanged', 1);

        $this->assertSame('S1', $resident->fresh()->pendidikan);
        $this->assertSame('Penduduk Uji', $resident->fresh()->nama_lengkap);
    }

    public function test_commit_rejects_a_different_file_than_the_preview(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $contents = $this->validCsv(1);
        $preview = $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($contents),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('dashboard.population-records.import'), [
            'file' => $this->upload(str_replace('Budi Santoso', 'Nama Berubah', $contents)),
            'preview_token' => $preview->json('token'),
        ])->assertStatus(422);
    }

    public function test_wna_without_travel_document_is_reported_per_row(): void
    {
        $user = User::factory()->create(['role' => 'operator']);
        $contents = str_replace(';WNI;;;;;O', ';WNA;;;;;O', $this->validCsv(1));

        $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($contents),
        ])->assertOk()
            ->assertJsonPath('preview.summary.valid', 0)
            ->assertJsonPath('preview.rows.0.issues.0.code', 'wna_document_required');
    }

    public function test_move_is_previewed_without_deleting_the_source_household_history(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $oldKk = '3326010101010099';
        $newKk = '3326010101010001';
        $this->createResident('3326010101800001', $oldKk);
        $contents = $this->validCsv(1);

        $preview = $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($contents),
        ])->assertOk()
            ->assertJsonPath('preview.rows.0.action', 'move')
            ->assertJsonPath('preview.summary.residents_moved', 1);

        $this->actingAs($user)->postJson(route('dashboard.population-records.import'), [
            'file' => $this->upload($contents),
            'preview_token' => $preview->json('token'),
        ])->assertOk()
            ->assertJsonPath('result.residents_moved', 1);

        $this->assertDatabaseHas('households', ['no_kk' => $oldKk]);
        $this->assertDatabaseHas('households', ['no_kk' => $newKk]);
        $this->assertDatabaseHas('population_records', ['nik' => '3326010101800001', 'no_kk' => $newKk]);
        $this->assertSame(0, Household::query()->where('no_kk', $oldKk)->firstOrFail()->currentMembers()->count());
    }

    public function test_expired_preview_token_is_rejected(): void
    {
        $now = Carbon::parse('2026-09-14 09:00:00');
        Carbon::setTestNow($now);

        try {
            $user = User::factory()->create(['role' => 'admin']);
            $contents = $this->validCsv(1);
            $preview = $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
                'file' => $this->upload($contents),
            ])->assertOk();

            Carbon::setTestNow($now->copy()->addMinutes(16));
            $this->actingAs($user)->postJson(route('dashboard.population-records.import'), [
                'file' => $this->upload($contents),
                'preview_token' => $preview->json('token'),
            ])->assertStatus(422)
                ->assertJsonFragment(['message' => 'Pratinjau import sudah kedaluwarsa. Silakan lakukan pratinjau ulang.']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_unauthorized_role_cannot_preview_population_import(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $this->upload($this->validCsv(1)),
        ])->assertForbidden();
    }

    private function validCsv(int $rows): string
    {
        $header = 'no_kk;nama_kepala_keluarga;alamat;rt;rw;kode_pos;dusun;desa;kecamatan;kabupaten;provinsi;no_urut_kk;status_hubungan;nik;nama_lengkap;jenis_kelamin;tempat_lahir;tanggal_lahir;agama;pendidikan;jenis_pekerjaan;status_perkawinan;kewarganegaraan;no_paspor;no_kitas_kitap;nama_ayah;nama_ibu;golongan_darah';
        $lines = [$header];
        for ($index = 1; $index <= $rows; $index++) {
            $relationship = $index === 1 ? 'Kepala Keluarga' : 'Anak';
            $name = $index === 1 ? 'Budi Santoso' : 'Anggota '.$index;
            $nik = '332601010180'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);
            $lines[] = "3326010101010001;Budi Santoso;Alamat Dusun;001;002;51164;Bojongireng;Desa Lambanggelun;Kecamatan Paninggaran;Kabupaten Pekalongan;Provinsi Jawa Tengah;{$index};{$relationship};{$nik};{$name};Laki-laki;Pekalongan;01-01-2000;Islam;SMA;Petani;Belum Kawin;WNI;;;;;O";
        }

        return implode("\n", $lines)."\n";
    }

    private function upload(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('penduduk.csv', $contents);
    }

    private function createResident(string $nik, string $noKk, ?string $education = null): PopulationRecord
    {
        $resident = PopulationRecord::query()->create([
            'full_name' => 'Penduduk Uji', 'nama_lengkap' => 'Penduduk Uji', 'nik' => $nik,
            'nkk' => $noKk, 'no_kk' => $noKk, 'birth_place' => 'Pekalongan', 'tempat_lahir' => 'Pekalongan',
            'birth_date' => '1990-01-01', 'tanggal_lahir' => '1990-01-01', 'gender' => 'Laki-laki',
            'jenis_kelamin' => 'Laki-laki', 'hamlet' => 'Bojongireng', 'dusun' => 'Bojongireng',
            'religion' => 'Islam', 'agama' => 'Islam', 'occupation' => 'Petani', 'pekerjaan' => 'Petani',
            'jenis_pekerjaan' => 'Petani', 'pendidikan' => $education, 'status_perkawinan' => 'Belum Kawin',
            'status_hubungan' => 'Kepala Keluarga', 'kewarganegaraan' => 'WNI', 'rt' => '001', 'rw' => '002',
            'desa' => PopulationRecord::DEFAULT_VILLAGE, 'kecamatan' => PopulationRecord::DEFAULT_DISTRICT,
            'kabupaten' => PopulationRecord::DEFAULT_REGENCY, 'provinsi' => PopulationRecord::DEFAULT_PROVINCE,
            'kode_pos' => PopulationRecord::DEFAULT_POSTAL_CODE,
        ]);

        app(\App\Services\PopulationHouseholdSyncService::class)->sync($resident, [
            'no_kk' => $noKk, 'nama_kepala_keluarga' => 'Penduduk Uji', 'alamat' => 'Alamat Uji',
            'rt' => '001', 'rw' => '002', 'kode_pos' => PopulationRecord::DEFAULT_POSTAL_CODE,
            'dusun' => 'Bojongireng', 'desa' => PopulationRecord::DEFAULT_VILLAGE,
            'kecamatan' => PopulationRecord::DEFAULT_DISTRICT, 'kabupaten' => PopulationRecord::DEFAULT_REGENCY,
            'provinsi' => PopulationRecord::DEFAULT_PROVINCE, 'status_hubungan' => 'Kepala Keluarga', 'no_urut_kk' => 1,
        ]);

        return $resident;
    }
}
