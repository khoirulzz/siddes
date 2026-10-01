# CDN gambar publik SIDDes

Worker ini melayani gambar Cloudinary publik pada `cdn.desalambanggelun.id`.
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

Worker hanya menerima gambar `image/upload` dari cloud `dcf6mkq3q` dan
`dzrca841f`. Jika cloud baru ditambahkan, daftar di Worker dan
`app/Support/PublicMedia.php` harus diperbarui bersama. Dokumen PDF penduduk,
arsip, dan aset privat tetap memakai rute aplikasi yang memeriksa akses.

## Pemeriksaan

```sh
node --test test/index.test.js
curl -I https://cdn.desalambanggelun.id/dcf6mkq3q/image/upload/v1774983115/171715_1_dfqfby.webp
curl -I https://cdn.desalambanggelun.id/dzrca841f/image/upload/v1782540973/welcome_epvmbq.webp
```
