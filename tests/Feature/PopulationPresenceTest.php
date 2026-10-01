<?php

namespace Tests\Feature;

use App\Models\PopulationRecord;
use App\Models\Household;
use App\Models\User;
use App\Services\PopulationHouseholdSyncService;
use App\Support\LetterSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PopulationPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_status_change_keeps_master_and_updates_subtables_and_statistics(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $resident = $this->resident();

        $this->actingAs($operator)->put(route('dashboard.population-records.update', $resident),
            $this->formData($resident, 'meninggal', 'Diploma IV/Sarjana'))->assertRedirect()->assertSessionHasNoErrors();
        $resident->refresh();
        $this->assertSame('meninggal', $resident->status_keberadaan);
        $this->assertSame('Diploma IV/Sarjana', $resident->pendidikan_update);

        $this->actingAs($operator)->get(route('dashboard.population-records.index', ['view' => 'individual']))
            ->assertOk()->assertSee($resident->nik)->assertSee('population-row--inactive');
        $this->actingAs($operator)->get(route('dashboard.population-records.index', ['view' => 'deceased']))
            ->assertOk()->assertSee($resident->nik);
        $this->actingAs($operator)->get(route('dashboard.population-records.index', ['view' => 'moved']))
            ->assertOk()->assertDontSee($resident->nik);
        $this->actingAs($operator)->getJson(route('dashboard.population-records.statistics'))
            ->assertOk()->assertJsonPath('genders.data.0', 0);
        $this->get(route('information.population'))->assertOk()->assertViewHas('totalResidents', 0);

        $this->actingAs($operator)->put(route('dashboard.population-records.update', $resident),
            $this->formData($resident, 'ditemukan', 'Diploma IV/Sarjana'))->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($operator)->get(route('dashboard.population-records.index', ['view' => 'deceased']))
            ->assertOk()->assertDontSee($resident->nik);
        $this->actingAs($operator)->getJson(route('dashboard.population-records.statistics'))
            ->assertOk()->assertJsonPath('genders.data.0', 1)
            ->assertJsonPath('education.data.5', 1)
            ->assertJsonPath('education.data.0', 0);
    }

    public function test_education_update_uses_chart_categories_and_can_be_cleared_or_omitted(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $resident = $this->resident();
        $resident->update(['pendidikan_update' => 'SMA']);

        $edit = $this->actingAs($operator)->get(route('dashboard.population-records.edit', $resident))
            ->assertOk()
            ->assertSee('name="pendidikan_update"', false)
            ->assertSee('SMA/Sederajat')
            ->assertSee('Belum diperbarui (gunakan pendidikan KK)');
        $this->assertMatchesRegularExpression('/<option value="SMA\/Sederajat" selected>/', $edit->getContent());

        $this->actingAs($operator)->put(route('dashboard.population-records.update', $resident),
            $this->formData($resident, 'ditemukan', 'SMA/Sederajat'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('SMA/Sederajat', $resident->fresh()->pendidikan_update);
        $this->assertSame('SD', $resident->fresh()->pendidikan);
        $this->actingAs($operator)->getJson(route('dashboard.population-records.statistics'))
            ->assertOk()->assertJsonPath('education.data.2', 1);

        $withoutOptionalField = $this->formData($resident, 'ditemukan', '');
        unset($withoutOptionalField['pendidikan_update']);
        $this->actingAs($operator)->put(route('dashboard.population-records.update', $resident), $withoutOptionalField)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('SMA/Sederajat', $resident->fresh()->pendidikan_update);

        $this->actingAs($operator)->put(route('dashboard.population-records.update', $resident),
            $this->formData($resident, 'ditemukan', ''))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($resident->fresh()->pendidikan_update);
        $this->actingAs($operator)->getJson(route('dashboard.population-records.statistics'))
            ->assertOk()->assertJsonPath('education.data.0', 1);

        $this->actingAs($operator)->put(route('dashboard.population-records.update', $resident),
            $this->formData($resident, 'ditemukan', 'Tidak valid'))
            ->assertSessionHasErrors('pendidikan_update');
        $this->assertNull($resident->fresh()->pendidikan_update);
    }

    public function test_inactive_nik_cannot_be_verified_or_submit_letters_on_web_and_mobile(): void
    {
        $resident = $this->resident();
        $resident->update(['status_keberadaan' => 'pindah']);
        $letter = [
            'nik' => $resident->nik,
            'phone' => '081234567890',
            'letter_type' => LetterSchema::TYPE_SKU,
            'dynamic_data' => ['nama_usaha' => 'Toko Uji', 'keperluan' => 'Uji'],
        ];

        $this->getJson(route('services.api.check-nik', ['nik' => $resident->nik]))->assertNotFound();
        $this->getJson('/api/v1/check-nik?nik='.$resident->nik)->assertNotFound();
        $this->post(route('services.letter.store'), $letter)->assertRedirect()->assertSessionHas('error');
        $this->postJson('/api/v1/letters', $letter)->assertNotFound();
        $this->assertDatabaseCount('letter_service_requests', 0);
    }

    public function test_import_preserves_missing_status_column_and_defaults_blank_or_invalid_to_found(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $resident = $this->resident();
        $resident->update(['status_keberadaan' => 'pindah', 'pendidikan_update' => 'SMA']);
        $base = "no_kk;nik;nama_lengkap\n{$resident->no_kk};{$resident->nik};{$resident->nama_lengkap}\n";

        $this->importCsv($operator, $base);
        $this->assertSame('pindah', $resident->fresh()->status_keberadaan);
        $this->assertSame('SMA', $resident->fresh()->pendidikan_update);

        $blank = "no_kk;nik;nama_lengkap;pendidikan_update;status_keberadaan\n{$resident->no_kk};{$resident->nik};{$resident->nama_lengkap};;\n";
        $this->importCsv($operator, $blank);
        $this->assertSame('ditemukan', $resident->fresh()->status_keberadaan);
        $this->assertSame('SMA', $resident->fresh()->pendidikan_update);

        $moved = "no_kk;nik;nama_lengkap;status_keberadaan\n{$resident->no_kk};{$resident->nik};{$resident->nama_lengkap};PINDAH\n";
        $this->importCsv($operator, $moved);
        $this->assertSame('pindah', $resident->fresh()->status_keberadaan);

        $invalid = "no_kk;nik;nama_lengkap;pendidikan_update;status_keberadaan\n{$resident->no_kk};{$resident->nik};{$resident->nama_lengkap};Diploma III;tidak valid\n";
        $this->importCsv($operator, $invalid);
        $this->assertSame('ditemukan', $resident->fresh()->status_keberadaan);
        $this->assertSame('Diploma III', $resident->fresh()->pendidikan_update);
    }

    public function test_pdf_documents_are_private_and_follow_resident_without_duplicate_subtable_data(): void
    {
        config([
            'cloudinary.enabled' => true,
            'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key',
            'cloudinary.api_secret' => 'secret',
            'cloudinary.delivery_base_url' => 'https://cdn.desalambanggelun.id',
        ]);
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
        Http::fake(function ($request) use (&$pdf) {
            if (str_ends_with($request->url(), '/raw/upload')) {
                return Http::response([
                    'asset_id' => 'resident-asset-1',
                    'public_id' => 'sid/population-documents/akta.pdf',
                    'resource_type' => 'raw',
                    'type' => 'authenticated',
                ]);
            }
            if (str_contains($request->url(), '/asset/download')) {
                return Http::response($pdf, 200, ['Content-Type' => 'application/pdf']);
            }
            if (str_ends_with($request->url(), '/raw/destroy')) {
                return Http::response(['result' => 'ok']);
            }

            return Http::response([], 404);
        });
        $operator = User::factory()->create(['role' => 'operator']);
        $resident = $this->resident();
        $resident->update(['status_keberadaan' => 'meninggal']);

        $this->actingAs($operator)->post(route('dashboard.population-documents.store', $resident), [
            'jenis' => 'surat_pindah',
            'document' => UploadedFile::fake()->createWithContent('salah.pdf', $pdf),
        ])->assertSessionHasErrors('jenis');
        $this->assertDatabaseCount('population_documents', 0);

        $this->actingAs($operator)->post(route('dashboard.population-documents.store', $resident), [
            'jenis' => 'akta_kematian',
            'document' => UploadedFile::fake()->createWithContent('akta.pdf', $pdf),
        ])->assertRedirect();
        $document = $resident->documents()->firstOrFail();
        $this->assertSame('resident-asset-1', $document->cloudinary_asset_id);
        $this->assertSame('sid/population-documents/akta.pdf', $document->cloudinary_public_id);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('population_documents', 'content_base64'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/raw/upload')
            && str_contains($request->body(), 'authenticated')
            && str_contains($request->body(), 'sid/population-documents'));
        $this->actingAs($operator)->get(route('dashboard.population-documents.show', [$resident, $document]))
            ->assertRedirectContains('https://cdn.desalambanggelun.id/private/pdf/resident-asset-1?');
        config(['cloudinary.delivery_base_url' => '']);
        $this->actingAs($operator)->get(route('dashboard.population-documents.show', [$resident, $document]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=akta.pdf')
            ->assertSee('%PDF-1.4', false);
        auth()->logout();
        $this->get(route('dashboard.population-documents.show', [$resident, $document]))->assertRedirect();

        $resident->update(['status_keberadaan' => 'ditemukan']);
        $this->actingAs($operator)->get(route('dashboard.population-records.index', ['view' => 'deceased']))
            ->assertOk()->assertDontSee($resident->nik);
        $this->actingAs($operator)->get(route('dashboard.population-records.edit', $resident))
            ->assertOk()->assertSee('Dokumen Tersimpan')->assertSee('Akta Kematian');
        $this->actingAs($operator)->post(route('dashboard.population-documents.store', $resident), [
            'jenis' => 'akta_kematian',
            'document' => UploadedFile::fake()->createWithContent('akta-baru.pdf', $pdf),
        ])->assertForbidden();
        $this->assertDatabaseCount('population_documents', 1);
        $this->actingAs($operator)->delete(route('dashboard.population-records.destroy', $resident))
            ->assertSessionHas('error');
        $this->actingAs($operator)->delete(route('dashboard.population-documents.destroy', [$resident, $document]))
            ->assertSessionHas('success');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/raw/destroy')
            && $request['public_id'] === 'sid/population-documents/akta.pdf'
            && $request['type'] === 'authenticated');
        $this->actingAs($operator)->delete(route('dashboard.population-records.destroy', $resident))
            ->assertRedirect();
        $this->assertDatabaseCount('population_records', 0);
    }

    public function test_pdf_upload_requires_cloudinary_and_rejects_a_public_asset(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $resident = $this->resident();
        $resident->update(['status_keberadaan' => 'pindah']);
        $upload = fn (): UploadedFile => UploadedFile::fake()->createWithContent(
            'pindah.pdf', "%PDF-1.4\n%%EOF\n"
        );

        $this->actingAs($operator)->post(route('dashboard.population-documents.store', $resident), [
            'jenis' => 'surat_pindah', 'document' => $upload(),
        ])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('population_documents', 0);

        config([
            'cloudinary.enabled' => true,
            'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key',
            'cloudinary.api_secret' => 'secret',
        ]);
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/raw/upload')) {
                return Http::response([
                    'asset_id' => 'public-asset', 'public_id' => 'public.pdf',
                    'resource_type' => 'raw', 'type' => 'upload',
                ]);
            }

            return Http::response(['result' => 'ok']);
        });

        $this->actingAs($operator)->post(route('dashboard.population-documents.store', $resident), [
            'jenis' => 'surat_pindah', 'document' => $upload(),
        ])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('population_documents', 0);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/raw/destroy')
            && $request['public_id'] === 'public.pdf'
            && $request['type'] === 'upload');
    }

    public function test_deleting_a_household_removes_current_members_and_documents_but_keeps_former_members(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $first = $this->resident();
        $second = $this->resident('3326010101800002');
        $former = $this->resident('3326010101800003');
        $household = Household::query()->where('no_kk', $first->no_kk)->firstOrFail();

        app(PopulationHouseholdSyncService::class)->sync($former, [
            'no_kk' => '3326010101010002', 'status_hubungan' => 'Kepala Keluarga',
            'dusun' => 'Bojongireng', 'nama_kepala_keluarga' => 'Warga Pindah KK',
        ]);
        $newHousehold = $former->fresh()->currentMembership->household;
        $second->documents()->create([
            'jenis' => 'surat_pindah', 'status_keberadaan' => 'pindah',
            'original_name' => 'pindah.pdf', 'size' => 50,
            'cloudinary_asset_id' => 'asset-2',
            'cloudinary_public_id' => 'sid/population-documents/pindah.pdf',
        ]);

        $this->actingAs($operator)->get(route('dashboard.population-households.show', $household))
            ->assertOk()->assertSee('Hapus KK dan seluruh anggota');
        $this->actingAs($operator)->delete(route('dashboard.population-households.destroy', $household))
            ->assertSessionHasErrors('confirm_no_kk');
        $this->actingAs($operator)->delete(route('dashboard.population-households.destroy', $household), [
            'confirm_no_kk' => '3326010101010099',
        ])->assertSessionHasErrors('confirm_no_kk');
        $this->assertDatabaseCount('population_records', 3);

        config([
            'cloudinary.enabled' => true, 'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key', 'cloudinary.api_secret' => 'secret',
        ]);
        Http::fake(['*/raw/destroy' => Http::response(['result' => 'ok'])]);

        $this->actingAs($operator)->delete(route('dashboard.population-households.destroy', $household), [
            'confirm_no_kk' => $household->no_kk,
        ])->assertRedirect(route('dashboard.population-records.index', ['view' => 'kk']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('households', ['id' => $household->id]);
        $this->assertDatabaseMissing('population_records', ['id' => $first->id]);
        $this->assertDatabaseMissing('population_records', ['id' => $second->id]);
        $this->assertDatabaseHas('population_records', ['id' => $former->id]);
        $this->assertDatabaseHas('households', ['id' => $newHousehold->id]);
        $this->assertDatabaseCount('population_documents', 0);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/raw/destroy')
            && $request['public_id'] === 'sid/population-documents/pindah.pdf'
            && $request['type'] === 'authenticated');
    }

    public function test_household_remains_when_cloudinary_rejects_document_deletion(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $resident = $this->resident();
        $household = Household::query()->where('no_kk', $resident->no_kk)->firstOrFail();
        $resident->documents()->create([
            'jenis' => 'surat_pindah', 'status_keberadaan' => 'pindah',
            'original_name' => 'pindah.pdf', 'size' => 50,
            'cloudinary_asset_id' => 'asset-3',
            'cloudinary_public_id' => 'sid/population-documents/pindah.pdf',
        ]);
        config([
            'cloudinary.enabled' => true, 'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key', 'cloudinary.api_secret' => 'secret',
        ]);
        Http::fake(['*/raw/destroy' => Http::response(['error' => ['message' => 'Rejected']], 401)]);

        $this->actingAs($operator)->delete(route('dashboard.population-households.destroy', $household), [
            'confirm_no_kk' => $household->no_kk,
        ])->assertSessionHas('error');
        $this->assertDatabaseHas('households', ['id' => $household->id]);
        $this->assertDatabaseHas('population_records', ['id' => $resident->id]);
        $this->assertDatabaseCount('population_documents', 1);
    }

    private function importCsv(User $operator, string $contents): void
    {
        $upload = fn (): UploadedFile => UploadedFile::fake()->createWithContent('penduduk.csv', $contents);
        $preview = $this->actingAs($operator)->postJson(route('dashboard.population-records.import.preview'), [
            'file' => $upload(),
        ]);
        $preview->assertOk()->assertJsonPath('preview.summary.invalid', 0);
        $this->actingAs($operator)->postJson(route('dashboard.population-records.import'), [
            'file' => $upload(), 'preview_token' => $preview->json('token'),
        ])->assertOk();
    }

    private function resident(string $nik = '3326010101800001'): PopulationRecord
    {
        $resident = PopulationRecord::query()->create([
            'full_name' => 'Warga Uji', 'nama_lengkap' => 'Warga Uji',
            'nik' => $nik, 'nkk' => '3326010101010001', 'no_kk' => '3326010101010001',
            'birth_place' => 'Pekalongan', 'tempat_lahir' => 'Pekalongan',
            'birth_date' => '1990-01-01', 'tanggal_lahir' => '1990-01-01',
            'gender' => 'Laki-laki', 'jenis_kelamin' => 'Laki-laki',
            'hamlet' => 'Bojongireng', 'dusun' => 'Bojongireng',
            'religion' => 'Islam', 'agama' => 'Islam',
            'occupation' => 'Petani', 'pekerjaan' => 'Petani', 'jenis_pekerjaan' => 'Petani',
            'status_hubungan' => 'Kepala Keluarga', 'status_perkawinan' => 'Belum Kawin',
            'kewarganegaraan' => 'WNI', 'pendidikan' => 'SD',
        ]);
        app(PopulationHouseholdSyncService::class)->sync($resident, [
            'no_kk' => $resident->no_kk, 'status_hubungan' => 'Kepala Keluarga',
            'dusun' => 'Bojongireng', 'nama_kepala_keluarga' => 'Warga Uji',
        ]);

        return $resident;
    }

    private function formData(PopulationRecord $resident, string $status, string $education): array
    {
        return [
            'nama_lengkap' => $resident->nama_lengkap, 'nik' => $resident->nik, 'no_kk' => $resident->no_kk,
            'nama_kepala_keluarga' => 'Warga Uji', 'alamat' => null, 'rt' => null, 'rw' => null,
            'kode_pos' => null, 'desa' => null, 'kecamatan' => null, 'kabupaten' => null,
            'provinsi' => null, 'no_urut_kk' => null,
            'dusun' => 'Bojongireng', 'status_hubungan' => 'Kepala Keluarga',
            'jenis_kelamin' => 'Laki-laki', 'tempat_lahir' => 'Pekalongan', 'tanggal_lahir' => '1990-01-01',
            'agama' => 'Islam', 'pendidikan' => 'SD', 'pendidikan_update' => $education,
            'status_keberadaan' => $status, 'jenis_pekerjaan' => 'Petani',
            'status_perkawinan' => 'Belum Kawin', 'kewarganegaraan' => 'WNI',
            'no_paspor' => null, 'no_kitas_kitap' => null, 'nama_ayah' => null,
            'nama_ibu' => null, 'golongan_darah' => null,
        ];
    }
}
