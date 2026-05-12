# Deploy Hardening Checklist (Shared Hosting)

1. Environment
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set `APP_URL` ke domain final (`https://...`)
- Pastikan `OPENROUTER_API_KEY` diisi dari `.env`, bukan hardcoded

2. Laravel cache
- Jalankan:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. File upload & storage
- Pastikan folder `storage/` writable
- Pastikan upload max sesuai kebutuhan hosting (PHP ini values):
  - `upload_max_filesize >= 8M`
  - `post_max_size >= 10M`
- Endpoint media publik sudah dibatasi MIME dan path prefix

4. Database
- Jalankan migrasi terbaru:
```bash
php artisan migrate --force
```
- Migration index performa harus sukses:
  - `2026_02_20_000004_add_performance_indexes_to_core_tables`

5. Security runtime
- Header keamanan aktif lewat middleware:
  - `X-Frame-Options`
  - `X-Content-Type-Options`
  - `Referrer-Policy`
  - `Permissions-Policy`
- Throttle login dan endpoint layanan publik sudah aktif

6. Opsional rekomendasi produksi
- Rotate API key OpenRouter lama jika pernah sempat tersimpan di repo
- Aktifkan HTTPS + redirect HTTP ke HTTPS
- Set backup database harian
- Monitor error log (`storage/logs/laravel.log`)

7. 