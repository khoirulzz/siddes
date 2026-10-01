# Rencana statistik kunjungan SIDDes

Tanggal kajian: 1 Oktober 2026.
Status: tahap pertama diimplementasikan pada checkout lokal; belum di-deploy.
Keputusan terbaru pengguna: ringkasan pengunjung di footer dahulu; laporan admin dan fitur kompleks menyusul.

Implementasi tahap pertama menampilkan hari ini, kemarin, bulan ini, dan total kunjungan. Menggunakan dua tabel untuk deduplikasi anonim dan rekap harian, cookie pihak pertama dengan fallback session, batas hari WIB, serta cache ringkasan 300 detik. Metrik tampilan halaman, laporan admin, dan grafik belum dibuat. Konfigurasi `VISITOR_STATISTICS_ENABLED` mengikuti lingkungan produksi secara default; dapat dimatikan dengan `false`. Jalankan migrasi sebelum aktivasi produksi. Hanya request yang mencapai Laravel yang dapat dicatat.

Validasi lokal: 10 test fitur statistik (62 assertions), seluruh suite PHP (78 tests, 553 assertions; batas memori CLI 512 MB dan ekstensi SQLite diaktifkan untuk test), pemeriksaan sintaks PHP, serta format file PHP baru lulus. Batas memori CLI bawaan menyebabkan pengujian impor penduduk berhenti melalui memory guard; suite lulus setelah penyesuaian lingkungan pengujian, tanpa perubahan pada modul impor. Belum ada verifikasi visual browser, konkurensi MySQL, atau produksi. Rencana di bawah mencakup tahap lanjutan juga.

## Hasil kajian

Widget tema Esensi OpenSID menampilkan hari ini, kemarin, dan jumlah pengunjung. Situs Desa Pener dan Makalu Selatan juga menampilkan pola ringkasan tersebut. Rujukan utama: [widget OpenSID](https://github.com/OpenSID/OpenSID/blob/umum/storage/app/themes/esensi/resources/views/widgets/statistik_pengunjung.blade.php), [Desa Pener](https://pener.id/index.php/), dan [Desa Makalu Selatan](https://makaluselatan.id/).

OpenSID memiliki laporan pengunjung untuk admin, termasuk rekap periode, grafik, cetak, dan unduh. Panduannya menyebut tanggal tanpa kunjungan bisa tidak muncul karena mengambil langsung baris database. SIDDes akan menampilkan tanggal tersebut sebagai nol. Rujukan: [panduan Pengunjung](https://panduan.opendesa.id/id/opensid/halaman-administrasi/admin-web/pengunjung) dan [controller laporan OpenSID](https://github.com/OpenSID/OpenSID/blob/umum/donjo-app/controllers/Pengunjung.php).

Model OpenSID yang diperiksa menyimpan rekap harian dalam `sys_traffic`; ringkasannya juga menyediakan informasi IP/browser. Kajian ini memakai tampilan dan kebutuhan laporannya sebagai referensi, sedangkan aturan pencatatan SIDDes dirancang sesuai aplikasi ini. Pemanggil counter OpenSID tidak diaudit seluruhnya, sehingga kesamaan definisi pengunjung tidak diasumsikan. Rujukan: [StatistikPengunjung OpenSID](https://github.com/OpenSID/OpenSID/blob/umum/app/Models/StatistikPengunjung.php).

Temuan pada checkout SIDDes:

- Laravel 12/PHP 8.3; belum ditemukan middleware, model, atau migrasi untuk menghitung pengunjung situs.
- `resources/views/layouts/public.blade.php` menyediakan footer bersama. CSS-nya berada di `public/assets/css/site.css`; footer saat ini memiliki empat kolom, menjadi dua kolom dan satu kolom pada layar kecil.
- `routes/web.php` memisahkan halaman publik, layanan, login, dashboard, media, dan `/health`. `/up` terdaftar di `bootstrap/app.php`. Android memakai `routes/api.php`.
- Dashboard memakai `auth` dan `role:admin,operator`; beberapa statistik sudah memakai cache. Grafik Chart.js sudah digunakan pada tampilan dashboard.
- `config/app.php` menetapkan UTC. Statistik kunjungan perlu batas hari khusus `Asia/Jakarta`.
- `.env.example` dan `render.yaml` memakai cache/session file. Blueprint menyebut Render dan Aiven MySQL. Ini bukti konfigurasi repositori, bukan verifikasi konfigurasi produksi aktif.
- `scripts/render-start.sh` menjalankan migrasi saat start, tetapi belum menjalankan scheduler. `phpunit.xml` memakai SQLite dalam memori.

## Cakupan bertahap

### Footer publik

Tambahkan satu panel **Statistik Kunjungan** sebagai baris di bawah empat blok footer yang sudah ada. Pada desktop angka menjadi empat item sejajar; pada ponsel menjadi grid dua kolom. Ikuti warna, tema terang/gelap, dan tipografi situs.

| Label | Makna |
| --- | --- |
| Hari ini | Browser berbeda yang tercatat pada tanggal WIB hari ini |
| Kemarin | Browser berbeda yang tercatat pada tanggal WIB kemarin |
| Bulan ini | Akumulasi pengunjung harian sejak tanggal 1 bulan berjalan |
| Total kunjungan | Akumulasi pengunjung harian sejak pencatatan diaktifkan |

Sertakan penjelasan ringkas: “Pengunjung dihitung sekali per browser setiap hari.” Cantumkan tanggal mulai pencatatan agar total memiliki konteks. Format angka Indonesia, misalnya `1.250`. Data dapat terlambat maksimal sekitar lima menit pada operasi normal karena cache.

Saat data tidak tersedia, tampilkan “Statistik sementara tidak tersedia”; angka nol hanya untuk periode yang benar-benar belum memiliki kunjungan. Tidak perlu menampilkan IP, browser, atau sistem operasi pengguna di footer.

### Dashboard admin (tahap lanjutan)

Tambahkan menu **Statistik Kunjungan** untuk admin dan operator dalam grup route dashboard yang sudah dilindungi.

- Ringkasan hari ini, kemarin, 7 hari terakhir, bulan ini, dan total kunjungan.
- Grafik harian dengan pilihan 7 hari atau 30 hari; default 30 hari.
- Dua seri: pengunjung harian dan tampilan halaman.
- Tabel tanggal, pengunjung harian, dan tampilan halaman; tanggal tanpa aktivitas bernilai nol.
- Filter rentang tanggal, maksimal 366 hari per permintaan, dengan paginasi tabel.
- Penjelasan cara hitung, zona waktu WIB, tanggal mulai, dan waktu pembaruan data.

Tunda ekspor/cetak, halaman terpopuler, asal pengunjung, pembagian perangkat, dan pengunjung online hingga kebutuhan lanjutan jelas. Pengunjung online membutuhkan pengukuran aktivitas berkala; tahap pertama cukup statistik harian.

## Definisi pencatatan

1. **Pengunjung harian:** satu identitas browser anonim dihitung sekali pada setiap tanggal WIB. Browser yang kembali besok menambah angka besok.
2. **Tampilan halaman (tahap lanjutan):** setiap GET yang berhasil menghasilkan HTML pada halaman publik yang diizinkan. Membuka beberapa halaman atau melakukan refresh menambah metrik ini. Tahap pertama hanya mencatat pengunjung harian.
3. **Rekap periode:** penjumlahan pengunjung harian, bukan jumlah orang unik dalam satu bulan atau sepanjang masa. Gunakan label “Total kunjungan” untuk rekap keseluruhan dan jelaskan definisinya.
4. **Identitas:** cookie pihak pertama berisi token acak, terpisah dari session login, berlaku 30 hari sejak dibuat; `HttpOnly`, `SameSite=Lax`, dan `Secure` pada HTTPS. Gunakan enkripsi cookie Laravel. Database hanya menerima HMAC token dengan tanggal WIB, bukan token mentah.
5. **Batas akurasi:** identitas mewakili browser. Menghapus cookie, mode privat, mengganti browser/perangkat, dan browser yang menolak cookie bisa menyebabkan penghitungan ulang. Pada cookie yang ditolak, gunakan fallback session bila tersedia; hasil tetap perkiraan. Tidak menyatukan pengguna berdasarkan IP karena koneksi warga dapat berbagi IP.
6. **Lingkungan:** pencatatan produksi diaktifkan melalui konfigurasi. Lokal/testing tidak masuk angka produksi; test dapat mengaktifkan tracker secara eksplisit.

Contoh: browser A membuka beranda, berita, dan profil pada hari yang sama → 1 pengunjung dan 3 tampilan halaman. Browser B membuka beranda → total hari itu 2 pengunjung dan 4 tampilan halaman. Browser A kembali besok → 1 pengunjung besok dan total kunjungan dua hari menjadi 3.

### Halaman yang dicatat

Gunakan daftar nama route yang diizinkan, bukan seluruh route `web`:

- `home`, `profile`, `information.population`, `information.activities`.
- `news.index`, `news.show`, `gallery.index`, `announcements.index`, `announcements.show`.
- `download.app`, `services.pbb`, `services.letter`, `services.complaint`.

Hanya GET dengan status 200 dan respons `text/html`. Abaikan HEAD, redirect, error, request JSON/XHR, prefetch yang dikenali, serta user agent bot/crawler/link preview/monitor yang dikenali. Filter bot bersifat upaya terbaik; bot yang menyamar tetap mungkin tercatat. Tetapkan pembatasan pencatatan untuk pola request berlebihan tanpa menolak halaman atau layanan warga.

Jangan catat login/dashboard, pengguna admin/operator yang sedang login, `/health`, `/up`, endpoint Android/API, chatbot, pencarian NIK/NOP/tiket, halaman sukses bertiket, unduhan surat/lampiran, POST formulir, media, dan aset. Tidak menyimpan query string, isi formulir, NIK, tiket, IP mentah, atau user agent mentah dalam tabel statistik.

## Rancangan teknis

Gunakan database aplikasi sebagai penyimpanan utama dan cache yang sudah tersedia untuk pembacaan ringkasan. Tahap pertama tidak membutuhkan layanan analytics, Redis baru, atau proses queue khusus.

| Tabel usulan | Isi dan indeks | Retensi |
| --- | --- | --- |
| `website_visitor_days` | `visit_date` WIB, `visitor_hash`; unique gabungan keduanya | Detail deduplikasi ditargetkan 35 hari |
| `website_daily_stats` | `visit_date` unik, `visitors`; `pageviews` dapat ditambahkan pada tahap lanjutan | Rekap harian tetap disimpan |

Penyimpanan rekap bertumbuh sekitar 365 baris per tahun; tabel deduplikasi bertumbuh sesuai jumlah browser yang berkunjung. Metrik periode membaca rekap harian sehingga tidak memindai seluruh detail pengunjung.

### Alur request

1. Middleware khusus pada route yang diizinkan memeriksa konfigurasi, autentikasi, metode, dan filter request.
2. Jalankan controller dan periksa respons akhirnya. Pencatatan tidak terjadi untuk halaman yang gagal/redirect.
3. Ambil/buat identitas cookie dan tentukan tanggal dengan zona waktu statistik. Set cookie pada respons melalui mekanisme cookie Laravel.
4. Dalam transaksi database, lakukan deduplikasi melalui unique constraint database; pastikan baris rekap tanggal tersedia dan naikkan `visitors` secara atomik hanya jika identitas hari itu baru. Session menyimpan marker keberhasilan untuk menghindari query ulang dalam hari yang sama. Deduplikasi database tetap berlaku ketika session hilang. `pageviews` menyusul pada tahap lanjutan.
5. Penanganan konflik harus mengabaikan konflik duplikat yang diharapkan saja. Jangan menelan kesalahan database lain sebagai seolah-olah pencatatan berhasil. Verifikasi implementasi pada MySQL selain SQLite; dokumentasi Laravel mengingatkan `insertOrIgnore` dapat mengabaikan error selain duplikat. [Rujukan Query Builder](https://laravel.com/framework/docs/12.x/queries#insert-statements).
6. Jika pencatatan gagal, pertahankan respons halaman, catat log kesalahan terbatas tanpa identitas pengunjung, dan jangan mengisi angka perkiraan. Pembacaan widget juga harus menangani kegagalan statistik/cache secara terpisah.

Cookie harus melewati middleware cookie Laravel. Urutan middleware harus diuji karena tracker berjalan pada route yang juga memakai session/auth. Ketika HTML selesai dirender sebelum pencatatan, footer dapat belum memasukkan kunjungan request tersebut; ini sesuai sifat ringkasan cache.

Gunakan `config/visitor_statistics.php` untuk `enabled`, `timezone`, tanggal aktivasi, lama cookie, retensi detail, dan TTL cache. Zona waktu statistik default `Asia/Jakarta`; tidak perlu mengubah zona waktu seluruh aplikasi.

Ringkasan publik memakai cache 300 detik dengan key berisi versi, lingkungan, dan tanggal WIB. Pergantian tanggal membuat key baru. Jangan menghapus cache pada setiap kunjungan. Cache menyimpan hasil rekap, bukan sumber angka atau satu-satunya mekanisme deduplikasi. Laporan admin dapat memakai cache 60 detik berdasarkan rentang tanggal. [Rujukan cache Laravel](https://laravel.com/framework/docs/12.x/cache#retrieve-and-store).

Sediakan command pembersihan detail lama dengan indeks tanggal dan batch terbatas. Untuk konfigurasi web tanpa scheduler saat ini, gunakan pembersihan batch saat ada traffic, dibatasi melalui cache, sampai backlog selesai. Pembersihan dapat terlambat ketika situs tidak aktif; 35 hari adalah target retensi, bukan janji penghapusan tepat waktu. Command manual harus tersedia; apabila dibutuhkan retensi ketat, jadwal eksekusi perlu disiapkan dan diverifikasi sebelum aktivasi. Pembersihan detail tidak mengurangi rekap/total.

Middleware GET menghitung request yang mencapai Laravel. Jika kelak HTML publik disajikan dari full-page cache/CDN, cakupan pencatatan harus ditinjau lagi dan dapat memerlukan endpoint browser tersendiri. Fitur tetap berjalan tanpa JavaScript pada rancangan awal.

## Urutan implementasi

### 1. Fondasi dan pencatatan

- Tambahkan konfigurasi, dua migrasi, model/service pencatatan, middleware, dan daftar route yang diizinkan.
- Tambahkan command pembersihan dan strategi batch untuk runtime tanpa scheduler.
- Buktikan deduplikasi, transaksi, increment atomik, kegagalan statistik, serta batas hari WIB sebelum menghubungkan UI.

### 2. Ringkasan footer

- Tambahkan service rekap dan view composer khusus layout publik, dengan cache dan penanganan error.
- Tambahkan partial statistik dan baris footer responsif di bawah grid yang ada.
- Verifikasi tema terang/gelap, angka panjang, kondisi nol, dan data tidak tersedia.

### 3. Laporan admin (tahap lanjutan)

- Tambahkan controller dan route `dashboard.visitor-statistics.index` dengan `auth` dan role yang sudah ada.
- Tambahkan menu di layout dashboard dan tampilan ringkasan, grafik Chart.js, filter, serta tabel.
- Isi tanggal kosong di service laporan; batasi rentang dan paginasi server.

### 4. Validasi dan aktivasi

- Jalankan test fitur/unit terkait, pemeriksaan route/view, dan build frontend jika aset Vite berubah.
- Uji browser desktop/ponsel, dua identitas browser, refresh, mode privat, dan pergantian hari.
- Uji request paralel dengan cookie yang sama pada MySQL: satu pengunjung per hari tanpa kehilangan kenaikan tampilan halaman. Test SQLite sendiri tidak membuktikan perilaku konkurensi MySQL.
- Ukur tambahan query dan waktu respons pada cache dingin/hangat. Tetapkan batas kinerja dari baseline SID saat implementasi; jangan mengklaim tanpa beban tambahan karena pencatatan tetap menulis database.
- Verifikasi database produksi, kebijakan cookie, migrasi, cache, dan pembersihan. Terapkan migrasi sebelum mengaktifkan flag produksi.
- Catat tanggal aktivasi sebenarnya. Data awal nol; belum ada data historis yang terbukti dapat diimpor. Riwayat sebelum aktivasi ditandai “belum dicatat”, bukan dianggap nol kunjungan.
- Periksa persistensi rekap setelah restart/deploy serta akses laporan tanpa login. Rollback dengan menonaktifkan flag; tabel dan data tetap dipertahankan.

## Kriteria selesai

- Browser yang sama membuka tiga halaman pada satu hari menghasilkan 1 pengunjung; browser lain menambah pengunjung baru. Tiga tampilan halaman menjadi kriteria tahap lanjutan.
- Pada 00.00 WIB kunjungan masuk hari baru, termasuk pergantian bulan/tahun.
- Request bot yang dikenali, health check, login/dashboard, API, POST, HEAD, serta respons gagal tidak mengubah rekap.
- Request bersamaan tidak menggandakan pengunjung atau menghilangkan increment.
- Hari tanpa kunjungan ditampilkan nol; hari sebelum aktivasi dan kegagalan statistik dibedakan dari nol.
- Penghapusan detail deduplikasi dan pembersihan cache tidak mengurangi total historis.
- Kegagalan komponen statistik tidak mengubah respons halaman yang berhasil dari controller.
- Footer terbaca pada desktop/ponsel dan dua tema; laporan hanya dapat diakses admin/operator.
- Laporan menyebut akumulasi pengunjung harian; tidak mengklaim menghitung orang unik sepanjang periode.

## Batas kajian ini

Kajian dan implementasi didasarkan pada checkout lokal dan sumber web yang diperiksa. Konfigurasi produksi, traffic aktual, full-page cache/CDN, dan kinerja endpoint live belum diverifikasi. Migrasi dijalankan pada SQLite terisolasi untuk pengujian/preview lokal. Database produksi belum diubah dan belum ada deployment.
