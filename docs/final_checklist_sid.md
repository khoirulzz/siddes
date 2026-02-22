# Final Checklist SID (Ringkas & Berurutan)

Dokumen ini merangkum revisi dari awal sampai akhir, plus checklist test lokal dan deploy shared hosting.

## 1) Ringkasan Revisi dari Awal sampai Akhir

### A. Layanan Surat Online (Rebuild)
- Alur surat diperbaiki end-to-end:
  - cek NIK -> auto isi identitas -> pilih jenis surat -> form dinamis -> submit
  - generate dokumen dari template -> download dokumen -> simpan nomor surat/tiket
- Pencarian surat ditambah (nomor surat + tiket).
- Dashboard admin menerima data surat masuk dan status bisa diupdate.
- Arsip surat dipisah dan hanya tersedia untuk status `Selesai`.

### B. Layanan PBB
- Form publik disederhanakan sesuai alur baru:
  - nama pemohon, nomor WhatsApp, multi NOP.
- Pencarian NOP/tiket diperbaiki.
- Admin detail PBB ditingkatkan (total NOP, total nominal, link WA).

### C. Layanan Pengaduan
- Validasi NIK ke data kependudukan aktif.
- Tiket pengaduan bisa dicari.
- Bukti lampiran bisa dilihat dari admin/publik (path handling diperbaiki).
- UI aksi admin dibuat lebih hemat ruang dan konsisten.

### D. Data Kependudukan
- Struktur tabel dan CRUD disesuaikan format kolom baru.
- Import template disesuaikan.
- Search NIK/No KK/NKK diperbaiki.

### E. Arsip Layanan
- Arsip dipisah per jenis: surat, PBB, pengaduan.
- Akses file arsip surat dibatasi status selesai.

### F. UI/UX (Admin & Publik)
- Dashboard admin: ringkasan card + badge notifikasi.
- Sidebar admin: fixed, icon, toggle collapse/expand, mobile-friendly.
- Footer publik didesain ulang (kontak + sosial media).
- Halaman publik berita/pengumuman/galeri dan profil desa dirapikan.

### G. AI Generator Berita/Pengumuman
- AI assist dibuat opsional (manual tetap bisa).
- Failover model AI 1 -> AI 2 diterapkan.
- Error AI dibuat jelas dan tidak menghalangi input manual.

### H. Media Upload & Kompresi
- Upload gambar berita/galeri/aktivitas/land/pengaduan dikompresi.
- URL media dipusatkan via endpoint internal media agar konsisten.

### I. Performa & Stabilitas
- Pagination ditambahkan pada tabel besar (admin + publik).
- Query berat di dashboard/layanan dioptimasi (agregasi + pengurangan raw query tidak perlu).
- Import kependudukan dioptimasi (cek duplikat bulk).
- Index performa database ditambahkan via migration.

### J. Security Hardening
- Throttling login + endpoint layanan publik aktif.
- Hardcoded API key dibersihkan dari config default.
- Security headers global ditambahkan.
- Validasi upload diperketat (`mimes` + `mimetypes` + batas dimensi untuk image).
- Endpoint file publik diverifikasi path prefix + MIME whitelist.
- Route storage private default Laravel dinonaktifkan.

## 2) Checklist Test Lokal (Sebelum Deploy)

### Fungsional
- [ ] Surat online: buat surat baru sampai download.
- [ ] Cari surat pakai tiket dan nomor surat.
- [ ] PBB: submit multi NOP dan cek status dari tiket.
- [ ] Pengaduan: submit dengan NIK valid + cek tiket + buka bukti.
- [ ] Arsip layanan: surat non-`Selesai` tidak bisa diakses PDF arsip.

### UI/UX
- [ ] Cek desktop + mobile untuk dashboard admin dan halaman publik.
- [ ] Cek sidebar toggle, card ringkasan, badge notifikasi.

### Keamanan dasar
- [ ] Login dibatasi throttle (cek route middleware `throttle:login`).
- [ ] Endpoint layanan publik pakai throttle lookup/submit.
- [ ] Upload file palsu (ekstensi benar tapi MIME salah) ditolak.

### Performa
- [ ] Tabel besar tampil paginated (tidak load semua sekaligus).
- [ ] Pencarian tabel besar tetap responsif.

## 3) Checklist Deploy Shared Hosting

### Environment
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://domain-kamu`
- [ ] `OPENROUTER_API_KEY` diisi di `.env` (jangan di config hardcoded)

### Command deploy (urutan aman)
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Permission folder
- [ ] `storage/` writable
- [ ] `bootstrap/cache/` writable

### Verifikasi cepat pasca deploy
- [ ] Halaman home tampil normal.
- [ ] Login admin bisa masuk.
- [ ] Upload berita/galeri berhasil.
- [ ] Download surat/pengaduan evidence berhasil.
- [ ] Route `storage/{path}` tidak aktif.

## 4) Catatan Operasional
- Jika API key OpenRouter lama pernah tersebar, lakukan rotate key.
- Untuk traffic rendah (<20/hari), konfigurasi sekarang sudah cukup aman dan ringan.
- Jalankan backup database berkala (minimal harian).
