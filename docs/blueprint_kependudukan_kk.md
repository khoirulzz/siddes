# Blueprint Kependudukan Berbasis Kartu Keluarga (KK)

## 1. Tujuan
Menyusun ulang data kependudukan agar:
- Isian mengikuti struktur KK resmi.
- Data tetap efisien untuk layanan digital (surat, pengaduan, lookup NIK).
- Mudah dioperasikan admin desa (CRUD + import).
- Aman dimigrasikan dari struktur saat ini tanpa memutus fitur yang sudah jalan.

## 2. Kondisi Sistem Saat Ini (Ringkas)
Saat ini data kependudukan disimpan 1 tabel `population_records` (per individu), dengan kolom utama:
- `nik`, `no_kk`, `nama_lengkap`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`
- `agama`, `pendidikan`, `pekerjaan`, `status_perkawinan`, `kewarganegaraan`
- `rt`, `rw`, `dusun`, `desa`, `kecamatan`, `kabupaten`, `provinsi`, `kode_pos`
- plus kolom kompatibilitas lama (`full_name`, `nkk`, `gender`, `hamlet`, dst).

Keterbatasan model lama:
- Data alamat/keluarga berulang di setiap anggota.
- Relasi keluarga tidak eksplisit.
- Sulit merepresentasikan 1 KK berisi banyak anggota dengan status hubungan.

## 3. Rekomendasi Model Data (Hybrid)
Gunakan 3 tabel inti:

### 3.1 `households` (Per KK)
Satu baris = satu kartu keluarga.

Kolom inti:
- `id` (PK)
- `no_kk` (UNIQUE, 16 digit)
- `nama_kepala_keluarga`
- `alamat`
- `rt`, `rw`
- `kode_pos`
- `dusun`, `desa`, `kecamatan`, `kabupaten`, `provinsi`
- `created_at`, `updated_at`

### 3.2 `residents` (Master Individu)
Satu baris = satu penduduk berdasarkan NIK.

Kolom inti:
- `id` (PK)
- `nik` (UNIQUE, 16 digit)
- `nama_lengkap`
- `jenis_kelamin` (`Laki-laki` / `Perempuan`)
- `tempat_lahir`
- `tanggal_lahir`
- `agama`
- `pendidikan`
- `jenis_pekerjaan`
- `status_perkawinan` (enum detail)
- `kewarganegaraan` (`WNI` / `WNA`)
- `no_paspor` (nullable)
- `no_kitas_kitap` (nullable)
- `nama_ayah` (nullable)
- `nama_ibu` (nullable)
- `golongan_darah` (`A`,`B`,`AB`,`O` atau null)
- `created_at`, `updated_at`

### 3.3 `household_members` (Relasi KK-Anggota)
Satu baris = 1 warga dalam 1 KK.

Kolom inti:
- `id` (PK)
- `household_id` (FK -> households.id)
- `resident_id` (FK -> residents.id)
- `status_hubungan` (Kepala Keluarga, Istri, Anak, Cucu, Orang Tua, Mertua, Famili Lain, Pembantu, Lainnya)
- `no_urut_kk` (urutan tampil di KK)
- `is_kepala_keluarga` (boolean)
- `created_at`, `updated_at`

Constraint yang disarankan:
- UNIQUE (`household_id`, `resident_id`)
- UNIQUE (`household_id`, `no_urut_kk`)
- maksimal 1 `is_kepala_keluarga=true` per `household_id` (enforce di service/validation).

## 4. Nilai Enum yang Disepakati
### 4.1 `status_perkawinan`
- `Belum Kawin`
- `Kawin Tercatat`
- `Kawin Belum Tercatat`
- `Cerai Hidup`
- `Cerai Mati`

### 4.2 `status_hubungan`
- `Kepala Keluarga`
- `Istri`
- `Anak`
- `Cucu`
- `Orang Tua`
- `Mertua`
- `Famili Lain`
- `Pembantu`
- `Lainnya`

### 4.3 `kewarganegaraan`
- `WNI`
- `WNA`

## 5. Alur Operasional yang Disarankan
### 5.1 Admin View Utama = Per KK
List menampilkan:
- No KK
- Nama Kepala Keluarga
- Alamat ringkas
- Jumlah anggota
- Aksi detail/edit/hapus

### 5.2 Detail KK
Halaman detail menampilkan:
- Blok atas data wilayah & alamat KK
- Tabel anggota keluarga (urutan `no_urut_kk`)
- Aksi tambah anggota, edit anggota, pindah anggota, hapus anggota

### 5.3 Form tambah anggota
Mode:
- Pilih penduduk existing (berdasarkan NIK), atau
- Buat penduduk baru lalu langsung link ke KK.

## 6. Desain Import Data (CSV/XLSX)
### 6.1 Format baris import
Satu baris = satu anggota keluarga.

Kolom import minimum:
- `no_kk`
- `nama_kepala_keluarga`
- `alamat`
- `rt`
- `rw`
- `kode_pos`
- `dusun`
- `desa`
- `kecamatan`
- `kabupaten`
- `provinsi`
- `no_urut_kk`
- `status_hubungan`
- `nik`
- `nama_lengkap`
- `jenis_kelamin`
- `tempat_lahir`
- `tanggal_lahir`
- `agama`
- `pendidikan`
- `jenis_pekerjaan`
- `status_perkawinan`
- `kewarganegaraan`
- `no_paspor`
- `no_kitas_kitap`
- `nama_ayah`
- `nama_ibu`
- `golongan_darah`

### 6.2 Mekanisme import
- Upsert `households` by `no_kk`.
- Upsert `residents` by `nik`.
- Upsert `household_members` by (`household_id`,`resident_id`).
- Validasi urutan dan kepala keluarga per KK.
- Simpan log hasil import: inserted, updated, skipped, error row.

## 7. Dampak ke Fitur SID Lain
### 7.1 Surat Online
Lookup `NIK` tetap dari tabel `residents`.
Alamat surat diambil dari relasi KK aktif:
- `residents` -> `household_members` -> `households`.

### 7.2 Pengaduan
Validasi NIK tetap valid.
Tidak perlu perubahan besar di alur tiket.

### 7.3 Statistik Kependudukan Publik
Agregasi tetap dari `residents`.
Jika perlu statistik per dusun, join via `household_members` + `households`.

## 8. Strategi Migrasi Aman (Bertahap)
### Tahap A (Non-breaking)
- Tambah tabel baru: `households`, `residents`, `household_members`.
- Buat service migration/backfill dari `population_records`.
- Belum mengubah fitur existing.

### Tahap B (Dual-read)
- Controller kependudukan baca dari tabel baru.
- Fitur surat/pengaduan tetap fallback ke `population_records` sementara.

### Tahap C (Switch)
- Semua fitur utama pakai tabel baru.
- `population_records` dijadikan legacy read-only sementara.

### Tahap D (Cleanup)
- Setelah stabil, pensiunkan `population_records` bertahap.

## 9. Rule Validasi Inti
- `no_kk`: numeric 16 digit.
- `nik`: numeric 16 digit.
- `tanggal_lahir`: valid date, tidak boleh > hari ini.
- `jenis_kelamin`: enum.
- `status_perkawinan`: enum detail.
- `kewarganegaraan`:
  - jika `WNA`, minimal salah satu `no_paspor` / `no_kitas_kitap` wajib.
- `status_hubungan=Kepala Keluarga`:
  - hanya 1 orang per KK.

## 10. Indexing & Performa
Index yang disarankan:
- `households`: unique `no_kk`, index `dusun`, `rt`, `rw`.
- `residents`: unique `nik`, index `nama_lengkap`, `tanggal_lahir`.
- `household_members`: index `household_id`, `resident_id`, unique composite.

## 11. Rencana UI/UX Admin
Urutan halaman kependudukan:
1. Ringkasan grafik
2. Tombol tambah KK / import
3. Search (No KK / NIK / Nama)
4. Tabel daftar KK (bukan daftar individu)
5. Klik KK => halaman detail anggota

Catatan UX:
- Tetap pertahankan dusun tabs cepat.
- Tabel detail anggota memiliki scroll internal.
- Aksi edit/hapus pakai icon compact agar hemat ruang.

## 12. Keputusan yang Perlu Dikunci Sebelum Eksekusi
1. Apakah satu penduduk boleh memiliki riwayat pindah KK (disarankan: ya, simpan riwayat nanti)?
2. Apakah import bersifat full replace per KK atau merge incremental (disarankan: merge incremental)?
3. Apakah kolom `alamat` di `households` cukup 1 field teks atau dipecah (jalan, nomor rumah, dsb)?

---
Dokumen ini adalah blueprint final untuk review sebelum implementasi kode.
