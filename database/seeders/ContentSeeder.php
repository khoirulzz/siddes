<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\ComplaintReport;
use App\Models\Gallery;
use App\Models\LandRecord;
use App\Models\LetterServiceRequest;
use App\Models\News;
use App\Models\PbbPaymentRequest;
use App\Models\PbbTaxObject;
use App\Models\PopulationRecord;
use App\Models\VillageActivity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        News::query()->delete();
        Announcement::query()->delete();
        Gallery::query()->delete();
        PopulationRecord::query()->delete();
        LandRecord::query()->delete();
        VillageActivity::query()->delete();
        PbbPaymentRequest::query()->delete();
        LetterServiceRequest::query()->delete();
        ComplaintReport::query()->delete();
        PbbTaxObject::query()->delete();

        News::create([
            'title' => 'Pentingnya Website Desa untuk Keterbukaan Informasi',
            'slug' => 'pentingnya-website-desa-untuk-keterbukaan-informasi',
            'excerpt' => 'Website desa menjadi kanal resmi untuk publikasi data, layanan, dan kegiatan pemerintahan desa.',
            'content' => 'Website desa membantu warga mengakses informasi kapan saja, mulai dari pengumuman, berita kegiatan, hingga data publik desa. Dengan kanal digital resmi, transparansi pemerintahan desa meningkat dan komunikasi dengan masyarakat menjadi lebih cepat.',
            'author_name' => 'Tim Desa Lambanggelun',
            'is_published' => true,
            'published_at' => Carbon::now()->subDays(2),
        ]);

        News::create([
            'title' => 'Pembuatan Website Desa Lambanggelun oleh Khoirul Ulum',
            'slug' => 'pembuatan-website-desa-lambanggelun-oleh-khoirul-ulum',
            'excerpt' => 'Website resmi desa sedang disusun untuk menghadirkan layanan informasi publik yang lebih rapi dan terstruktur.',
            'content' => 'Inisiatif pembuatan website Desa Lambanggelun oleh Khoirul Ulum difokuskan untuk menghadirkan portal resmi desa yang modern, formal, dan mudah digunakan. Tahap MVP 1 menyiapkan seluruh halaman publik dan dashboard admin/operator sebagai fondasi sistem.',
            'author_name' => 'Tim Desa Lambanggelun',
            'is_published' => true,
            'published_at' => Carbon::now()->subDay(),
        ]);

        News::create([
            'title' => 'Digitalisasi Layanan Desa Masuk Tahap MVP 2',
            'slug' => 'digitalisasi-layanan-desa-masuk-tahap-mvp-2',
            'excerpt' => 'Layanan PBB, Surat Online, dan Pengaduan kini dapat diajukan melalui website desa.',
            'content' => 'Pemerintah Desa Lambanggelun melanjutkan pengembangan website pada tahap MVP 2 dengan menghadirkan layanan digital inti. Warga dapat melakukan pengajuan pembayaran PBB, surat online, dan pengaduan masyarakat melalui portal resmi desa.',
            'author_name' => 'Admin Website Desa',
            'is_published' => true,
            'published_at' => Carbon::now()->subHours(20),
        ]);

        News::create([
            'title' => 'Komitmen Desa Lambanggelun pada Transparansi Data Publik',
            'slug' => 'komitmen-desa-lambanggelun-pada-transparansi-data-publik',
            'excerpt' => 'Informasi kependudukan, pertanahan, dan kegiatan desa disajikan dengan ringkasan visual yang mudah dipahami.',
            'content' => 'Website Desa Lambanggelun terus dikembangkan untuk memperkuat keterbukaan informasi publik. Masyarakat kini dapat memantau ringkasan data kependudukan, data pertanahan, serta progres kegiatan desa dan informasi anggaran secara lebih terstruktur.',
            'author_name' => 'Admin Website Desa',
            'is_published' => true,
            'published_at' => Carbon::now()->subHours(8),
        ]);

        Announcement::create([
            'title' => 'Website Desa Lambanggelun Tahap Pengembangan',
            'content' => 'Website resmi Desa Lambanggelun sedang dalam tahap pengembangan awal. Beberapa layanan akan diluncurkan bertahap.',
            'start_date' => Carbon::today(),
            'is_active' => true,
        ]);

        Gallery::create([
            'title' => 'Kegiatan Gotong Royong Warga',
            'image_url' => 'https://picsum.photos/seed/lambanggelun-1/1200/800',
            'description' => 'Warga bersama perangkat desa melaksanakan gotong royong kebersihan lingkungan.',
            'activity_date' => Carbon::today()->subDays(7),
        ]);

        Gallery::create([
            'title' => 'Musyawarah Desa',
            'image_url' => 'https://picsum.photos/seed/lambanggelun-2/1200/800',
            'description' => 'Kegiatan musyawarah desa membahas prioritas program pembangunan tahun berjalan.',
            'activity_date' => Carbon::today()->subDays(14),
        ]);

        PopulationRecord::insert([
            [
                'full_name' => 'Ahmad Fauzi',
                'nik' => '3326010101900001',
                'nkk' => '3326011201010001',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1990-01-01',
                'gender' => 'Laki-laki',
                'hamlet' => 'Bojongireng',
                'religion' => 'Islam',
                'occupation' => 'Petani',
                'address_detail' => 'RT 01/RW 02 Dusun Bojongireng',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Siti Aminah',
                'nik' => '3326011202920002',
                'nkk' => '3326011201010001',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1992-02-12',
                'gender' => 'Perempuan',
                'hamlet' => 'Bojongireng',
                'religion' => 'Islam',
                'occupation' => 'Ibu Rumah Tangga',
                'address_detail' => 'RT 01/RW 02 Dusun Bojongireng',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Rohman Hakim',
                'nik' => '3326010303850003',
                'nkk' => '3326011303050002',
                'birth_place' => 'Batang',
                'birth_date' => '1985-03-03',
                'gender' => 'Laki-laki',
                'hamlet' => 'Panumbangan',
                'religion' => 'Islam',
                'occupation' => 'Wiraswasta',
                'address_detail' => 'RT 03/RW 01 Dusun Panumbangan',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Nur Aini',
                'nik' => '3326011507880004',
                'nkk' => '3326011303050002',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1988-07-15',
                'gender' => 'Perempuan',
                'hamlet' => 'Panumbangan',
                'religion' => 'Islam',
                'occupation' => 'Guru',
                'address_detail' => 'RT 03/RW 01 Dusun Panumbangan',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Mochammad Ridwan',
                'nik' => '3326012006940005',
                'nkk' => '3326011208090003',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1994-06-20',
                'gender' => 'Laki-laki',
                'hamlet' => 'Mandelun',
                'religion' => 'Islam',
                'occupation' => 'Karyawan Swasta',
                'address_detail' => 'RT 02/RW 04 Dusun Mandelun',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Dewi Lestari',
                'nik' => '3326011110960006',
                'nkk' => '3326011208090003',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1996-10-11',
                'gender' => 'Perempuan',
                'hamlet' => 'Mandelun',
                'religion' => 'Islam',
                'occupation' => 'Pedagang',
                'address_detail' => 'RT 02/RW 04 Dusun Mandelun',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Budi Santoso',
                'nik' => '3326010808810007',
                'nkk' => '3326010109010004',
                'birth_place' => 'Kendal',
                'birth_date' => '1981-08-08',
                'gender' => 'Laki-laki',
                'hamlet' => 'Sasak',
                'religion' => 'Islam',
                'occupation' => 'Buruh Harian',
                'address_detail' => 'RT 04/RW 03 Dusun Sasak',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Indah Permata',
                'nik' => '3326012209900008',
                'nkk' => '3326010109010004',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1990-09-22',
                'gender' => 'Perempuan',
                'hamlet' => 'Sasak',
                'religion' => 'Islam',
                'occupation' => 'Perangkat Desa',
                'address_detail' => 'RT 04/RW 03 Dusun Sasak',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Slamet Riyadi',
                'nik' => '3326010107750009',
                'nkk' => '3326010505020005',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1975-07-01',
                'gender' => 'Laki-laki',
                'hamlet' => 'Simendem',
                'religion' => 'Islam',
                'occupation' => 'Peternak',
                'address_detail' => 'RT 01/RW 05 Dusun Simendem',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'full_name' => 'Lina Marlina',
                'nik' => '3326011812800010',
                'nkk' => '3326010505020005',
                'birth_place' => 'Pekalongan',
                'birth_date' => '1980-12-18',
                'gender' => 'Perempuan',
                'hamlet' => 'Simendem',
                'religion' => 'Islam',
                'occupation' => 'UMKM',
                'address_detail' => 'RT 01/RW 05 Dusun Simendem',
                'source_file' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        LandRecord::insert([
            [
                'land_code' => 'LMBG-TKD-001',
                'location' => 'Blok Utara Desa',
                'hamlet' => 'Bojongireng',
                'category' => 'Tanah Kas Desa',
                'area_m2' => 3200,
                'ownership_status' => 'Aset Desa',
                'owner_name' => 'Pemerintah Desa Lambanggelun',
                'certificate_number' => 'SHM/001/LBG/2019',
                'tax_object_number' => '33.26.010.001.001',
                'status' => 'Aktif',
                'description' => 'Lahan kas desa untuk kegiatan pertanian warga.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'land_code' => 'LMBG-AFU-002',
                'location' => 'Blok Barat Desa',
                'hamlet' => 'Panumbangan',
                'category' => 'Aset Fasilitas Umum',
                'area_m2' => 1450,
                'ownership_status' => 'Aset Desa',
                'owner_name' => 'Pemerintah Desa Lambanggelun',
                'certificate_number' => 'SHM/017/LBG/2020',
                'tax_object_number' => '33.26.010.001.017',
                'status' => 'Aktif',
                'description' => 'Lahan untuk area fasilitas kegiatan masyarakat.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        VillageActivity::insert([
            [
                'title' => 'Pembangunan Drainase Lingkungan Dusun Bojongireng',
                'slug' => 'pembangunan-drainase-lingkungan-dusun-bojongireng',
                'category' => 'Infrastruktur',
                'activity_date' => Carbon::create(2025, 1, 20)->toDateString(),
                'location' => 'Dusun Bojongireng',
                'person_in_charge' => 'Kasi Kesejahteraan',
                'status' => 'Selesai',
                'budget' => 185000000,
                'summary' => 'Pembangunan saluran drainase untuk mengurangi genangan saat musim hujan.',
                'description' => 'Kegiatan pembangunan drainase dilakukan sepanjang 450 meter di titik rawan genangan sebagai program prioritas infrastruktur desa.',
                'cover_image_path' => 'https://images.unsplash.com/photo-1494522358652-f30e61a60313?auto=format&fit=crop&w=1200&q=80',
                'document_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Pelatihan UMKM dan Digital Marketing Warga',
                'slug' => 'pelatihan-umkm-dan-digital-marketing-warga',
                'category' => 'Pemberdayaan Masyarakat',
                'activity_date' => Carbon::create(2025, 4, 12)->toDateString(),
                'location' => 'Balai Desa Lambanggelun',
                'person_in_charge' => 'Kasi Pelayanan',
                'status' => 'Selesai',
                'budget' => 47500000,
                'summary' => 'Pelatihan penguatan kapasitas UMKM desa untuk pemasaran digital.',
                'description' => 'Program pelatihan mencakup branding produk, foto produk, strategi marketplace, dan literasi keuangan bagi pelaku UMKM desa.',
                'cover_image_path' => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1200&q=80',
                'document_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Normalisasi Saluran Irigasi Pertanian',
                'slug' => 'normalisasi-saluran-irigasi-pertanian',
                'category' => 'Pertanian',
                'activity_date' => Carbon::create(2025, 8, 8)->toDateString(),
                'location' => 'Area Sawah Dusun Simendem',
                'person_in_charge' => 'Kasi Pemerintahan',
                'status' => 'Berjalan',
                'budget' => 96000000,
                'summary' => 'Kegiatan normalisasi saluran irigasi sebagai dukungan produktivitas pertanian.',
                'description' => 'Kegiatan meliputi pembersihan sedimentasi, perbaikan tanggul kecil, dan penataan alur irigasi primer menuju petak sawah warga.',
                'cover_image_path' => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=1200&q=80',
                'document_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Festival Budaya dan Kesenian Desa',
                'slug' => 'festival-budaya-dan-kesenian-desa',
                'category' => 'Sosial Budaya',
                'activity_date' => Carbon::create(2026, 2, 2)->toDateString(),
                'location' => 'Lapangan Desa Lambanggelun',
                'person_in_charge' => 'Sekretaris Desa',
                'status' => 'Perencanaan',
                'budget' => null,
                'summary' => 'Agenda budaya tahunan sebagai ruang ekspresi seni warga desa.',
                'description' => 'Kegiatan festival budaya disiapkan untuk menampilkan kesenian lokal, bazar UMKM, serta promosi potensi desa ke masyarakat luas.',
                'cover_image_path' => 'https://images.unsplash.com/photo-1472653431158-6364773b2a56?auto=format&fit=crop&w=1200&q=80',
                'document_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        PbbPaymentRequest::insert([
            [
                'applicant_name' => 'Hadi Suparto',
                'nik' => '3326010101900011',
                'nop' => '33.26.010.001.201',
                'tax_year' => 2025,
                'amount_due' => 175000,
                'phone' => '081298001122',
                'email' => 'hadi@example.com',
                'payment_method' => 'Bank Transfer',
                'proof_path' => null,
                'notes' => 'Mohon verifikasi pembayaran tahap 1.',
                'status' => 'Diajukan',
                'admin_notes' => null,
                'submitted_at' => Carbon::now()->subDays(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'applicant_name' => 'Dian Kartini',
                'nik' => '3326011103910012',
                'nop' => '33.26.010.001.245',
                'tax_year' => 2025,
                'amount_due' => 240000,
                'phone' => '081355667788',
                'email' => null,
                'payment_method' => 'Tunai',
                'proof_path' => null,
                'notes' => null,
                'status' => 'Diproses',
                'admin_notes' => 'Menunggu konfirmasi bendahara.',
                'submitted_at' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        LetterServiceRequest::insert([
            [
                'applicant_name' => 'Solehudin',
                'nik' => '3326010708890013',
                'kk_number' => '3326011208090033',
                'letter_type' => 'Surat Keterangan Domisili',
                'purpose' => 'Administrasi kerja',
                'address' => 'RT 02/RW 04 Dusun Mandelun',
                'phone' => '081311223344',
                'email' => 'solehudin@example.com',
                'attachment_path' => null,
                'status' => 'Diajukan',
                'admin_notes' => null,
                'requested_at' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'applicant_name' => 'Maya Sari',
                'nik' => '3326011209940014',
                'kk_number' => '3326010505020045',
                'letter_type' => 'Surat Keterangan Usaha',
                'purpose' => 'Pengajuan KUR',
                'address' => 'RT 01/RW 05 Dusun Simendem',
                'phone' => '082100778899',
                'email' => null,
                'attachment_path' => null,
                'status' => 'Diproses',
                'admin_notes' => 'Validasi data lapangan.',
                'requested_at' => Carbon::now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        ComplaintReport::insert([
            [
                'ticket_code' => 'PGD-' . Carbon::now()->subDays(2)->format('ymd') . '-A1B2',
                'reporter_name' => 'Rendi Prakoso',
                'phone' => '081277889900',
                'email' => 'rendi@example.com',
                'subject' => 'Lampu jalan mati di Dusun Sasak',
                'category' => 'Infrastruktur',
                'description' => 'Beberapa titik lampu jalan di RT 04 Dusun Sasak mati sejak pekan lalu.',
                'location' => 'RT 04/RW 03 Dusun Sasak',
                'evidence_path' => null,
                'status' => 'Diterima',
                'response' => null,
                'handled_by' => null,
                'submitted_at' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ticket_code' => 'PGD-' . Carbon::now()->subDay()->format('ymd') . '-C3D4',
                'reporter_name' => 'Fitri Ananda',
                'phone' => '081233445566',
                'email' => null,
                'subject' => 'Saluran air tersumbat',
                'category' => 'Infrastruktur',
                'description' => 'Saluran air di sekitar balai dusun Panumbangan tersumbat dan menimbulkan genangan.',
                'location' => 'Dusun Panumbangan',
                'evidence_path' => null,
                'status' => 'Diproses',
                'response' => 'Petugas dijadwalkan survei lapangan besok pagi.',
                'handled_by' => 'Operator Desa',
                'submitted_at' => Carbon::now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Data Dummy Master PBB untuk Tes Search NOP
        PbbTaxObject::insert([
            [
                'nop' => '33.26.010.001.001',
                'tax_name' => 'Ahmad Fauzi',
                'tax_address' => 'Dusun Bojongireng RT 01/02',
                'tax_year' => 2025,
                'amount_due' => 45000,
                'status' => 'Belum Lunas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nop' => '33.26.010.001.002',
                'tax_name' => 'Siti Aminah',
                'tax_address' => 'Dusun Bojongireng RT 01/02',
                'tax_year' => 2025,
                'amount_due' => 60000,
                'status' => 'Belum Lunas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
