<?php

namespace Database\Seeders;

use App\Models\PopulationRecord;
use App\Services\PopulationHouseholdSyncService;
use Illuminate\Database\Seeder;

class PopulationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $residents = [
            ['nik' => '3326010101800001', 'nama_lengkap' => 'Budi Santoso', 'jenis_kelamin' => 'Laki-laki', 'tanggal_lahir' => '1980-01-01', 'status_hubungan' => 'Kepala Keluarga', 'no_urut_kk' => 1, 'jenis_pekerjaan' => 'Petani'],
            ['nik' => '3326014502850002', 'nama_lengkap' => 'Siti Aminah', 'jenis_kelamin' => 'Perempuan', 'tanggal_lahir' => '1985-02-05', 'status_hubungan' => 'Istri', 'no_urut_kk' => 2, 'jenis_pekerjaan' => 'Ibu Rumah Tangga'],
            ['nik' => '3326014706100003', 'nama_lengkap' => 'Rina Lestari', 'jenis_kelamin' => 'Perempuan', 'tanggal_lahir' => '2010-06-07', 'status_hubungan' => 'Anak', 'no_urut_kk' => 3, 'jenis_pekerjaan' => 'Pelajar/Mahasiswa'],
        ];

        $sync = app(PopulationHouseholdSyncService::class);
        foreach ($residents as $data) {
            $payload = array_merge([
                'no_kk' => '3326010101010001',
                'nama_kepala_keluarga' => 'Budi Santoso',
                'alamat' => 'RT 001 RW 002 Dusun Bojongireng',
                'rt' => '001', 'rw' => '002', 'kode_pos' => PopulationRecord::DEFAULT_POSTAL_CODE,
                'dusun' => 'Bojongireng', 'desa' => PopulationRecord::DEFAULT_VILLAGE,
                'kecamatan' => PopulationRecord::DEFAULT_DISTRICT, 'kabupaten' => PopulationRecord::DEFAULT_REGENCY,
                'provinsi' => PopulationRecord::DEFAULT_PROVINCE, 'tempat_lahir' => 'Pekalongan',
                'agama' => 'Islam', 'pendidikan' => null, 'status_perkawinan' => 'Belum Kawin',
                'kewarganegaraan' => 'WNI', 'no_paspor' => null, 'no_kitas_kitap' => null,
                'nama_ayah' => null, 'nama_ibu' => null, 'golongan_darah' => null,
            ], $data);

            $resident = PopulationRecord::query()->updateOrCreate(['nik' => $data['nik']], [
                'full_name' => $data['nama_lengkap'], 'nama_lengkap' => $data['nama_lengkap'],
                'nkk' => $payload['no_kk'], 'no_kk' => $payload['no_kk'],
                'birth_place' => $payload['tempat_lahir'], 'tempat_lahir' => $payload['tempat_lahir'],
                'birth_date' => $data['tanggal_lahir'], 'tanggal_lahir' => $data['tanggal_lahir'],
                'gender' => $data['jenis_kelamin'], 'jenis_kelamin' => $data['jenis_kelamin'],
                'hamlet' => $payload['dusun'], 'dusun' => $payload['dusun'],
                'religion' => $payload['agama'], 'agama' => $payload['agama'],
                'occupation' => $data['jenis_pekerjaan'], 'pekerjaan' => $data['jenis_pekerjaan'],
                'jenis_pekerjaan' => $data['jenis_pekerjaan'], 'status_perkawinan' => $payload['status_perkawinan'],
                'status_hubungan' => $data['status_hubungan'], 'kewarganegaraan' => 'WNI',
                'rt' => $payload['rt'], 'rw' => $payload['rw'], 'desa' => $payload['desa'],
                'kecamatan' => $payload['kecamatan'], 'kabupaten' => $payload['kabupaten'],
                'provinsi' => $payload['provinsi'], 'kode_pos' => $payload['kode_pos'],
                'address_detail' => $payload['alamat'], 'source_file' => 'demo-seeder',
            ]);
            $sync->sync($resident, $payload);
        }
    }
}
