# Dokumentasi Implementasi SID Desa Lambanggelun (Versi Berhasil)

Tanggal snapshot: 22 Februari 2026
Lokasi proyek: `c:\laragon\www\webdes`

## 1. Ringkasan Proyek
SID (Sistem Informasi Desa) ini adalah portal layanan dan informasi publik Desa Lambanggelun yang menggabungkan:
- Website publik untuk informasi desa dan layanan online.
- Dashboard admin/operator untuk operasional data dan layanan.
- Alur surat online berbasis template DOCX + output PDF tanpa ketergantungan LibreOffice.
- Optimasi performa, keamanan dasar, dan UI/UX responsif (desktop + mobile), termasuk mode gelap untuk halaman publik.

Aplikasi ini ditujukan untuk trafik rendah-menengah (operasional desa), dan saat ini sudah stabil untuk localhost serta siap dipublikasikan ke shared hosting dengan konfigurasi yang tepat.

## 2. Teknologi yang Digunakan

## Backend
- PHP `^8.2`
- Laravel `^12`
- MySQL/MariaDB (konfigurasi default Laravel, migration aktif)

## Frontend
- Blade template engine (Laravel)
- CSS kustom (`public/assets/css/site.css`, `public/assets/css/dashboard.css`)
- Vanilla JavaScript (interaksi UI, AJAX layanan, toggle tema, copy ticket, polling dashboard)
- Chart.js (visualisasi grafik publik dan admin)
- Bootstrap Icons + Animate.css (penunjang UI)

## Build Tooling
- Vite (`vite`, `laravel-vite-plugin`)
- NPM scripts: `npm run dev`, `npm run build`

## Paket/Library Inti
- `phpoffice/phpword` (pengisian template surat DOCX)
- `mpdf/mpdf` (renderer PDF utama)
- `dompdf/dompdf` (fallback PDF renderer)
- `maatwebsite/excel` (import data kependudukan dan template CSV)

## 3. Arsitektur Modul

## Modul Publik
- Beranda (grafik ringkasan + layanan + berita + pengumuman + galeri)
- Profil desa (profil, visi-misi, struktur organisasi, perangkat, batas wilayah, peta)
- Informasi publik:
  - Kependudukan (ringkasan + grafik + tabel rekap)
  - Pertanahan (ringkasan + grafik saja, detail internal)
  - Kegiatan desa
- Layanan online:
  - Surat online
  - PBB
  - Pengaduan
- Halaman konten:
  - Berita + detail artikel
  - Pengumuman + detail pengumuman
  - Galeri

## Modul Dashboard (Admin/Operator)
- Ringkasan monitoring berbasis card + badge notifikasi
- Manajemen data: kependudukan, pertanahan, kegiatan, berita, pengumuman, galeri, master PBB
- Layanan masuk: surat online, PBB, pengaduan
- Arsip layanan: surat, PBB, pengaduan
- AI assist untuk draft berita/pengumuman
- Manajemen operator (khusus role admin)

## 4. Fitur Inti yang Sudah Berhasil

## 4.1 Layanan Surat Online
Alur berjalan:
1. User input NIK.
2. Sistem validasi NIK dan tarik identitas dari data kependudukan.
3. User pilih jenis surat.
4. Form dinamis muncul sesuai jenis surat.
5. Submit pengajuan.
6. Sistem generate nomor tiket + nomor surat resmi.
7. Template DOCX (`/docs/*.docx`) diisi otomatis via placeholder.
8. User bisa download DOCX/PDF.
9. Data surat masuk ke dashboard admin.

Detail implementasi:
- Jenis surat: SKU, SKD, SKK, SPK, SKTM.
- Skema field dinamis di `app/Support/LetterSchema.php`.
- Placeholder info ada di `docs/info_placeholder.md`.
- Nomor surat resmi format `NNN/KODE/LMBG/BULAN_ROMAWI/TAHUN`.
- Tanggal placeholder `{tanggal}` sudah format lengkap (tanggal-bulan-tahun).
- PDF menggunakan mPDF (fallback dompdf), bukan LibreOffice.
- Admin validasi manual status/ttd tetap didukung melalui workflow status.

## 4.2 Layanan PBB
Alur berjalan:
1. User isi nama + nomor WhatsApp aktif.
2. User input multi NOP.
3. Tiap NOP dapat dicari/validasi dulu.
4. Submit permohonan, sistem membuat nomor tiket PBB.
5. User dapat melacak status dengan nomor tiket.

Detail implementasi:
- NOP multi-entry dengan validasi backend.
- Dashboard menampilkan detail: pemohon, link WA, total NOP, total nominal.
- Master data PBB sudah dipagination + search.

## 4.3 Layanan Pengaduan
Alur berjalan:
1. User input NIK + data pengaduan.
2. NIK divalidasi harus terdaftar pada data kependudukan.
3. Upload bukti (image/pdf/video) didukung.
4. Sistem membuat tiket pengaduan.
5. User dapat lacak status dengan tiket.
6. Admin dapat melihat detail, lampiran bukti, update status.

## 4.4 Pelacakan & Copy Nomor Tiket/Surat
- Pencarian status tersedia untuk:
  - Surat (nomor surat resmi atau tiket)
  - PBB (tiket)
  - Pengaduan (tiket)
- Tombol salin otomatis (`copy`) tersedia pada nomor tiket/nomor surat.

## 4.5 Arsip Layanan
- Struktur arsip disiapkan:
  - `storage/app/public/archives/surat`
  - `storage/app/public/archives/pbb`
  - `storage/app/public/archives/pengaduan`
- PDF arsip surat hanya tersedia jika status surat `Selesai`.
- Arsip PBB/pengaduan saat ini berfungsi sebagai arsip data layanan di halaman admin.

## 4.6 AI Generator Berita & Pengumuman
- AI assist tersedia di form admin berita/pengumuman.
- AI bersifat opsional, operator tetap bisa tulis manual penuh.
- Mekanisme model failover: model utama -> model fallback.
- Error handling ramah operator (sibuk, timeout, format invalid, server error).
- Riwayat generate tersimpan pada tabel `ai_generations`.
- Pengumuman mendukung:
  - `berlaku` opsional (start/end date nullable)
  - `link_url` opsional
  - halaman detail pengumuman terpisah
- Thumbnail pengumuman menggunakan ikon default variabel konfigurasi.

## 4.7 Media Upload & Kompresi
Kompresi image aktif untuk modul:
- Berita (thumbnail)
- Galeri
- Kegiatan desa (cover/dokumen image)
- Pertanahan (foto/dokumen image)
- Pengaduan (evidence image)

Teknis:
- `ImageUploadService` resize + kompres (GD) ke WebP/JPG.
- Jika GD tidak tersedia, fallback ke upload normal.
- Akses media publik lewat endpoint aman `/media/public/{path}`.

## 5. Desain dan UX (Versi Implementasi)

## Publik
- Tema visual modern, responsif mobile-first.
- Header diperbarui: hamburger mobile + toggle dark mode.
- Dark mode/Light mode konsisten dengan kontras teks, card, form, dan chart.
- Navigasi mobile sudah dirapikan termasuk submenu.
- Footer profesional: kontak resmi, sosial media, maps.
- Carousel berita/galeri mendukung auto-slide + manual navigation.
- Halaman baca artikel berita menggunakan container putih untuk keterbacaan.

## Dashboard
- Sidebar fixed, icon menu, toggle expand/collapse (desktop & mobile), state tersimpan localStorage.
- Ringkasan monitoring berbentuk card berwarna + badge notifikasi merah.
- Filter periode monitoring: hari ini, kemarin, 7 hari, bulan ini, tahun ini.
- Refresh data monitoring via polling berkala (aman untuk shared hosting).

## 6. Data Kependudukan (Skema Aktual)
Kolom utama yang dipakai:
- `nik`
- `no_kk`
- `nama_lengkap`
- `jenis_kelamin`
- `tempat_lahir`
- `tanggal_lahir`
- `agama`
- `pendidikan`
- `pekerjaan`
- `status_perkawinan`
- `kewarganegaraan`
- `rt`
- `rw`
- `dusun`
- `desa`
- `kecamatan`
- `kabupaten`
- `provinsi`
- `kode_pos`
- `address_detail`

Default otomatis jika kosong:
- Desa: `Desa Lambanggelun`
- Kecamatan: `Kecamatan Paninggaran`
- Kabupaten: `Kabupaten Pekalongan`
- Provinsi: `Provinsi Jawa Tengah`
- Kode pos: `51164`

Fitur admin kependudukan:
- CRUD lengkap
- Import CSV/XLSX + deduplikasi NIK
- Download template CSV
- Search by NIK / No KK / NKK
- Pagination + ringkasan grafik

## 7. Keamanan yang Sudah Diterapkan
- Hidden admin login path (`ADMIN_LOGIN_PATH`), route `/login` bisa dijadikan decoy 404.
- Trigger akses admin dari publik via interaksi logo (double click/double tap).
- Role middleware (`admin`, `operator`).
- Rate limiting:
  - login
  - service lookup
  - service submit
- Security headers global middleware:
  - `X-Frame-Options`
  - `X-Content-Type-Options`
  - `Referrer-Policy`
  - `Permissions-Policy`
  - `Cross-Origin-*`
  - `Strict-Transport-Security` saat HTTPS
- Validasi upload ketat (`mimes`, `mimetypes`, ukuran, dimensi image).
- Endpoint file publik pakai allowlist path prefix + MIME whitelist (`MediaSecurity`).

## 8. Optimasi Performa yang Sudah Diterapkan
- Pagination tabel besar (kependudukan, master PBB, layanan, konten).
- Index database tambahan untuk kolom pencarian/filter utama.
- Query dashboard monitoring dioptimasi dengan agregasi SQL.
- Kompresi gambar untuk menghemat storage dan bandwidth.
- Caching browser untuk media publik (`Cache-Control`).
- Polling monitoring interval 30 detik (lebih aman daripada realtime infra tambahan untuk shared hosting).

## 9. Konfigurasi Penting

## File Konfigurasi
- `config/village.php`: profil desa, logo, kontak, sosial media, maps, icon pengumuman.
- `config/ai.php`: provider OpenRouter, model utama/fallback, prompt default.
- `config/security.php`: admin login path, media allowlist.

## ENV Kritis
- `APP_ENV`, `APP_DEBUG`, `APP_URL`
- `ADMIN_LOGIN_PATH`
- `OPENROUTER_API_KEY`
- `OPENROUTER_MODEL_PRIMARY`
- `OPENROUTER_MODEL_FALLBACK`
- variabel branding desa sesuai kebutuhan

## 10. Peta File Kustomisasi Cepat
- Routing utama: `routes/web.php`
- Form dinamis surat: `app/Support/LetterSchema.php`
- Generator dokumen surat: `app/Services/LetterDocumentService.php`
- Template surat DOCX: folder `docs/`
- Info placeholder surat: `docs/info_placeholder.md`
- Layanan publik (logic): `app/Http/Controllers/PublicServiceController.php`
- UI publik: `resources/views/public/*`
- Layout publik + dark mode + nav logic: `resources/views/layouts/public.blade.php`
- CSS publik: `public/assets/css/site.css`
- Dashboard admin: `resources/views/layouts/dashboard.blade.php`
- CSS dashboard: `public/assets/css/dashboard.css`
- AI content endpoint: `app/Http/Controllers/Admin/AiContentController.php`
- AI service core: `app/Services/AI/AiWriterService.php`
- Upload kompresi gambar: `app/Services/ImageUploadService.php`
- Endpoint media aman: `app/Http/Controllers/PublicMediaController.php`

## 11. Status Implementasi dan Kesiapan Deploy
Status saat ini:
- Fitur utama SID sudah terimplementasi dan terintegrasi.
- Alur surat online, PBB, pengaduan, konten publik, dan dashboard admin sudah berjalan.
- Optimasi performa serta hardening keamanan dasar sudah diterapkan.

Kondisi siap deploy jika:
1. Environment production diatur benar.
2. Migration dan cache command dijalankan.
3. Permission `storage/` dan `bootstrap/cache/` writable.
4. API key AI valid (jika fitur AI ingin aktif).

## 12. Catatan Operasional
- Untuk kebutuhan desa dengan trafik harian rendah, arsitektur saat ini sudah cukup efisien.
- Disarankan backup database rutin dan rotasi API key bila pernah terekspos.
- Jika nanti dibutuhkan realtime penuh, dapat ditingkatkan ke websocket service; namun versi sekarang dipilih yang paling aman dan ringan untuk shared hosting.
