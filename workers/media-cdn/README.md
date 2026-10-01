# CDN media SIDDes

Worker ini melayani gambar dan PDF Cloudinary publik pada `cdn.desalambanggelun.id`.
Path setelah hostname sama dengan path Cloudinary, misalnya:

`https://cdn.desalambanggelun.id/dcf6mkq3q/image/upload/v1/foto.webp`

berasal dari:

`https://res.cloudinary.com/dcf6mkq3q/image/upload/v1/foto.webp`.

## Pemasangan

Jalankan dari direktori ini dengan akun Cloudflare yang memiliki Worker
`subwebdes-worker` dan zona `desalambanggelun.id`:

```sh
npx wrangler deploy --keep-vars
```

Konfigurasi `wrangler.jsonc` memasang route `cdn.desalambanggelun.id/*` pada
record DNS CDN yang sudah diproksikan Cloudflare. `--keep-vars` mempertahankan
secret Worker yang sebelumnya dibuat lewat dashboard. Jangan arahkan trafik CDN
ke Worker sebelum route ini aktif; CNAME ke `workers.dev` saja dapat menghasilkan
HTTP 522.

SIDDes memakai `https://cdn.desalambanggelun.id` sebagai alamat tampilan
gambar Cloudinary publik. Untuk kembali menampilkan URL Cloudinary langsung,
set `CLOUDINARY_DELIVERY_BASE_URL` ke string kosong pada lingkungan Laravel,
lalu segarkan konfigurasi aplikasi. URL asli tetap tersimpan di database.

Worker menerima gambar `image/upload` serta PDF `raw/upload` dari cloud
`dcf6mkq3q` dan `dzrca841f`. PDF publik ditampilkan langsung di browser.
Jika cloud baru ditambahkan, daftar di Worker dan `app/Support/PublicMedia.php`
harus diperbarui bersama.

Dokumen PDF penduduk disimpan sebagai aset Cloudinary `authenticated`. Operator
atau admin tetap membuka rute aplikasi yang memeriksa sesi dan relasi dokumen
ke warga. Aplikasi lalu menerbitkan URL CDN `/private/pdf/<asset_id>` dengan
parameter `exp` dan tanda tangan HMAC SHA-256 yang berlaku lima menit. Worker
memeriksa tanda tangan tersebut, mengambil PDF melalui Cloudinary Upload API,
dan mengirimnya `inline` dengan `no-store`. URL CDN yang sudah diterbitkan dapat
dibuka oleh pemegang URL sampai masa berlakunya habis; jangan bagikan URL itu.
Worker memerlukan `CLOUDINARY_API_KEY` dan `CLOUDINARY_API_SECRET` yang sama
dengan aplikasi Laravel, serta `CLOUDINARY_CLOUD_NAME` dari `wrangler.jsonc`.
Jika konfigurasi CDN dinonaktifkan, aplikasi memakai pengiriman PDF melalui
Laravel sebagai cadangan.
PDF arsip surat dan file lain yang dibuka melalui rute aplikasi tetap mengikuti
aturan akses serta mode unduh pada rute masing-masing.

## Pemeriksaan

```sh
node --test test/index.test.js
curl -I https://cdn.desalambanggelun.id/dcf6mkq3q/image/upload/v1774983115/171715_1_dfqfby.webp
curl -I https://cdn.desalambanggelun.id/dzrca841f/image/upload/v1782540973/welcome_epvmbq.webp
```
