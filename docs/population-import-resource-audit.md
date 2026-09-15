# Audit resource import kependudukan — 15 September 2026

## Bukti file

Lampiran pertama identik byte demi byte; pengguna mengonfirmasi salah salin lalu
mengganti lampiran gagal. Perbandingan berikut memakai file gagal yang diperbarui:

| Metrik sheet Data | Berhasil | Gagal (diperbarui) |
| --- | ---: | ---: |
| Ukuran XLSX (byte) | 277.718 | 756.482 |
| Ukuran XML sheet (byte) | 2.158.641 | 6.377.116 |
| Record sel | 80.123 | 280.028 |
| Sel dengan elemen nilai/formula/inline string | 163 | 364 |
| Baris dengan elemen nilai, termasuk header | 6 | 13 |
| Dimensi sheet | A1:AB10001 | A1:AB10001 |

File gagal menyimpan seluruh persegi panjang 28 kolom x 10.001 baris sebagai
record sel, meskipun baris penduduk hanya 12. Baris kosong mendominasi beban.

Parser lama memuat semua sheet beserta sel kosong berformat, lalu memanggil
`getCell()` untuk 28 kolom sampai baris terakhir sebelum menyaring baris kosong.
PhpSpreadsheet membuat objek sel yang belum ada saat `getCell()` dipanggil.
Dengan demikian, kedua template dapat mengembangkan sekitar 280.000 objek sel
selama pemeriksaan. Pada file gagal, record tersebut sudah dimuat pada tahap load,
bersamaan dengan XML yang lebih besar; pada file berhasil banyak sel baru dibuat
sesudah load. Ini terjadi sebelum commit dan bisa sebelum query database.

Respons 502/503 dan email batas memori konsisten dengan proses server terhenti,
tetapi log startup saja belum membuktikan penyebab restart di Render. Ukuran RSS
instance, kondisi request lain, dan batas PHP berbeda dari ukuran file unggahan.

## Perubahan

- Excel dibaca satu sheet dan satu batch 500 baris; tahap pencarian header dibatasi
  15 baris pertama dan 128 kolom. Header alias, tipe identifier, serta format tanggal
  tetap digunakan. Sheet Data tetap diprioritaskan.
- Sel kosong tidak dimuat jika reader dapat mengabaikannya, dan iterasi tidak lagi
  membuat objek untuk koordinat yang tidak ada. Workbook dilepas setelah tiap batch.
- XLSX dipindai sebagai XML berarus lebih dulu untuk menemukan hanya rentang baris
  yang memiliki nilai atau rumus. File lama dengan 10.001 baris format kosong tetap
  dapat diperiksa bila data sebenarnya tidak melampaui batas. XLS dibaca per batch
  karena format lamanya tidak mendukung pemindaian XML ini.
- Template baru menggunakan format kolom untuk teks/tanggal, bukan membuat puluhan
  ribu sel kosong. Dropdown dan filter tersedia hingga 6.000 baris data.
- Batas defensif sebelum membuka ZIP: 256 entry, 8 MiB per entry, 24 MiB total
  setelah dekompresi. Maksimal 8 sheet. File kompleks ditolak dengan instruksi
  memperkecil workbook atau memakai CSV, bukan dipotong diam-diam.
- Area sheet terpilih di atas batas baris/kolom ditolak dengan pesan perbaikan.
  Ini juga dapat menolak workbook dengan format berlebih di luar data sebenarnya.
- Pemeriksaan budget memori di antara batch/tahap: maksimal tambahan 128 MiB dari
  awal request dan tetap menyisakan 48 MiB dari batas PHP. Ini pengaman tambahan, bukan
  jaminan pembatas RSS seluruh container atau perlindungan semua operasi native XML.
- Koleksi mentah hasil parser dilepas setelah normalisasi. Koleksi pencocokan
  database masih dikumpulkan, tetapi dicek terhadap budget di antara query batch.
- Preview dan commit memakai lock file per instance, tanpa Redis; request import
  bersamaan menerima 429. Lease 10 menit untuk pemulihan jika worker mati; respons
  normal maupun exception melepaskan lock. Halaman lain tidak dikunci.
- Error pembaca/preview dicatat pada level error dengan kelas exception, durasi,
  dan peak PHP memory; tidak mencatat isi baris. Pesan UI 502/503 tidak lagi
  menyatakan bahwa database penyebabnya.
- `.dockerignore` mengecualikan lampiran kependudukan, dump database, environment,
  dan artifact lokal dari image build. Tidak menghapus file lokal atau membersihkan
  data yang sudah terlanjur berada dalam image/repository lama.

Tidak menaikkan memory_limit, memperpendek timeout, mengubah aturan merge, atau
menjalankan perubahan pada database production.

## Verifikasi dan deployment

Pengujian parser mencakup CSV/TXT, XLS/XLSX, template sparse, batas antar-batch,
tanggal Excel, identifier teks/numerik, formula, batas baris dan expanded ZIP.
Pengujian feature memeriksa format template ringan serta lock dan pelepasannya.

Pengukuran parser lokal tanpa membaca database atau menampilkan data pribadi:

```sh
php -d memory_limit=256M scripts/benchmark-population-parser.php path/to/file.xlsx
php artisan test --filter=Population
npm run build
```

Setelah deploy, unduh template baru dan uji preview template lama juga. Cocokkan
waktu pengujian dengan grafik Memory dan Events Render. Ukuran PHP peak dari
benchmark bukan RAM total FrankenPHP/Render. Tidak memerlukan migrasi database.
