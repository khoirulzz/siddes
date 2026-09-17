# Pesan WhatsApp di admin SIDDes

## Arsitektur dan konfigurasi

Browser menghubungi Laravel saja. `MessagingService` menghubungi `/integration/v1` WAPBB dengan key server-side. Kontak, template, campaign, queue, dan session tetap berada di Neon. MySQL hanya menyimpan ledger `messaging_submissions` untuk rekonsiliasi create; bukan salinan kontak atau queue. Halaman memakai layout admin yang sama, tanpa iframe.

Pekerjaan ada pada branch `feature/whatsapp-messaging`; WAPBB pada `production/siddes-messaging`. Tidak ada push/deploy otomatis. React tetap menjadi panel cadangan.

Salin variabel dari `.env.messaging.example` ke environment Laravel:

```dotenv
MESSAGING_ENABLED=false
MESSAGING_API_URL=https://wapbb-api.onrender.com
MESSAGING_OPERATOR_API_KEY=<sama dengan SID_OPERATOR_API_KEY backend>
MESSAGING_ADMIN_API_KEY=<sama dengan SID_ADMIN_API_KEY backend>
```

Gunakan dua key acak yang berbeda, minimal 32 karakter. Key tersebut tidak boleh memakai prefix `VITE_`, masuk HTML, atau sama dengan secret dispatcher. `WEB_ORIGIN` WAPBB tetap untuk panel React; integrasi Laravel bersifat server-to-server sehingga tidak membutuhkan origin browser SIDDes di CORS WAPBB.

## Deployment bertahap

1. Hentikan API lama dan cron sementara; jangan jalankan API lokal dengan session Neon produksi.
2. Deploy branch WAPBB dan jalankan migration SQL 0005 sebelum API start. Pertahankan `WA_SESSION_ENCRYPTION_KEY` lama; jangan menggantinya saat redeploy. Pastikan `/health` sehat dan konfigurasi key admin/operator sudah tersedia.
3. Deploy SIDDes dengan feature flag mati, jalankan `php artisan migrate --force`, `npm ci`, `npm run build`, `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache`.
4. Aktifkan `MESSAGING_ENABLED=true`, ulangi config cache, lalu buka admin → Pesan WhatsApp. Operator tidak mendapat halaman koneksi; admin dapat pairing.
5. Siapkan pesan umum atau template `{{nama}}`, pilih penerima eksplisit, pratinjau, buat draft, lalu konfirmasi Mulai secara terpisah. Tidak ada auto-send saat membuat draft.
6. Pilot berizin: satu kontak, kemudian 5, 10, dan 25. Uji perangkat Android/iOS/Web sebelum perluasan; CTA Baileys tidak dijamin didukung semua versi.

Cron tetap POST setiap 10 menit, header `Authorization: Bearer <INTERNAL_DISPATCH_SECRET>`, `Content-Type: application/json`, body `{}`, timeout 30 detik. Warm-up GET `/health` pada menit 8,18,28,38,48,58 sebelum dispatch menit 0,10,20,30,40,50. Cold start Render dapat melampaui timeout; warm-up bukan jaminan. Health/heartbeat database membangunkan atau mempertahankan compute Neon; pertimbangkan biaya dan idle compute.

## Operasional dan kegagalan

### Perbaikan preview dan antarmuka (2026-09-17)

- Isi campaign dinormalisasi dari CRLF/CR ke LF di adapter Laravel, sebelum validasi, hash ledger dan forwarding. Preview JSON dan submit textarea HTML kini memakai isi identik. Spasi, baris kosong, token bertanda tangan, perubahan penerima/consent dan idempotensi tetap diperiksa; validasi snapshot backend tidak dilewati. Tidak membutuhkan migration atau redeploy WAPBB.
- Kontak/template memakai workspace responsif dengan form terpisah; checkbox memiliki ukuran tetap dan nama/nomor penerima dipisahkan. Riwayat, template dan isi campaign memakai ringkasan 110 karakter yang dapat dibuka/tutup melalui elemen details native. Isi tetap di-escape Blade, bukan HTML pesan.
- Template composer dimuat ketika picker dibuka, pilihan penerima tetap tersimpan lintas halaman/pencarian, dan hanya perubahan payload yang membatalkan preview. Loading, hitungan karakter, penerima kosong dan kegagalan daftar ditampilkan secara eksplisit.
- Refresh aktif ditahan ketika operator sedang berinteraksi, submitting atau membuka detail pesan. Tidak menambahkan request per ekspansi pesan, library UI, upload atau perubahan queue/dispatcher.
- Jika key pernah ditempelkan ke chat atau file example, perlakukan sebagai terekspos: buat pasangan baru di environment Render WAPBB/SIDDes. Example harus tetap tanpa key nyata; jangan mengganti encryption key session sebagai bagian dari rotasi key integrasi.

- Semua informasi memakai satu daftar kontak (nama perwakilan rumah dan nomor WhatsApp). Tidak terkait KK, NOP, pembayaran, multi-account atau multi-tenant.
- Kontak opt-out/nonaktif/nomor berubah sejak snapshot tidak direlay. Import CSV/XLSX maksimal 5 MB/1.000 baris; parser XLSX dimuat hanya saat digunakan. Persetujuan kosong berarti opt-out. Server memvalidasi ulang.
- Text-only default. Banner opsional adalah URL konfigurasi yang disnapshot ketika draft dibuat; bukan upload atau URL gambar bebas. Caption memakai isi pesan final.
- Status proses terpisah dari receipt: Diserahkan → Diterima server → Terkirim → Dibaca. Diserahkan bukan bukti sampai ke perangkat.
- Hasil `UNKNOWN` tidak diulang otomatis. Retry manual dapat menggandakan pesan; periksa penerima dahulu. Retry menggunakan generasi/provider ID baru agar receipt lama tidak mengubah status percobaan baru.
- Jika create timeout, form dan request UUID dipertahankan. Pilih Periksa status pembuatan draft; lookup memakai UUID yang sama. Jangan membuat key baru sebelum merekonsiliasi request yang belum pasti.
- Backend offline menampilkan 503/tidak tersedia, bukan daftar kosong yang seolah berhasil. Admin lainnya tetap dapat digunakan. Mutation gagal mempertahankan input.
- Refresh otomatis hanya ketika halaman terlihat (15 detik pada campaign/riwayat aktif; 5 detik selama pairing). Saat mengetik/submitting tidak reload; halaman idle menggunakan refresh manual.

## Rollback dan pengujian

Rollback SIDDes: set `MESSAGING_ENABLED=false` dan ulangi `config:cache`. Ledger dan migration boleh dipertahankan. Jangan menghapus data Neon untuk rollback.

Rollback WAPBB: pause campaign, hentikan cron dan instance baru, baru jalankan versi lama. Versi `main` tidak mendukung draft pesan langsung (`template_id=null`); jangan menjalankan campaign tersebut dari baseline lama. Kolom/tabel migration 0005 aditif, tetapi rollback kode tidak mengubah draft baru menjadi kompatibel secara semantik. Jangan menyalakan dua versi pada session yang sama; versi lama belum menghormati lease. Tunggu lease 90 detik setelah proses tidak berhenti bersih.

Validasi modul: `php artisan test --filter=MessagingTest` (Http::fake + SQLite terisolasi), `node --test resources/js/messaging-import.test.js`, dan `npm run build`. Test Http::fake tidak membuktikan pengiriman WhatsApp aktual. Browser desktop/mobile, koneksi API Render ke SIDDes nyata, cold start, logout HP, dan pilot perangkat masih harus diuji secara berizin sebelum aktivasi luas.

Checkpoint lokal: WAPBB `backup/pre-siddes-integration`, SIDDes `backup/pre-whatsapp-integration`. `main` WAPBB dan `master` SIDDes tetap menunjuk kode sebelum integrasi. Jangan memakai baseline lama untuk job hasil retry generasi baru tanpa peninjauan: versi lama tidak memahami generation/attempt metadata.
