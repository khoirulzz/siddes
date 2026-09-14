<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PublicAiChatController extends Controller
{
    private function buildVillageContext(): string
    {
        $villageName = config('village.name', 'Desa');
        $district    = config('village.district', '-');
        $phone       = config('village.phone', '-');
        $email       = config('village.email', '-');
        $website     = config('village.website_url', url('/'));
        $address     = config('village.address', '-');

        $latestNews = News::query()
            ->latest('published_at')
            ->limit(3)
            ->pluck('title')
            ->map(fn ($t) => '- ' . Str::limit($t, 80))
            ->implode("\n");

        $announcements = Announcement::active()
            ->latest()
            ->limit(2)
            ->pluck('title')
            ->map(fn ($t) => '- ' . Str::limit($t, 80))
            ->implode("\n");

        return <<<CONTEXT
Kamu adalah asisten AI resmi {$villageName}, {$district}.
Jawab pertanyaan warga dengan ramah, informatif, dan singkat dalam Bahasa Indonesia.
Jika ada link URL, sertakan lengkap agar bisa diklik pengguna.

=== DATA DESA ===
Nama Desa: {$villageName} | Wilayah: {$district}
Alamat: {$address} | Telepon: {$phone} | Email: {$email}
Website: {$website}

=== LAYANAN ONLINE ===
1. Surat Online → {$website}/layanan/surat-online
   Tersedia: Surat Keterangan Domisili, Kematian, Kelahiran, Tidak Mampu, Pengantar, dll.
   Proses: Isi NIK → Pilih jenis surat → Isi form → Ajukan → Dapat nomor tiket → Surat diproses 1-3 hari kerja.
2. PBB (Pajak Bumi Bangunan) → {$website}/layanan/pbb
3. Pengaduan Warga → {$website}/layanan/pengaduan
4. Lacak Status Pengajuan: gunakan nomor tiket di form layanan masing-masing.
5. Download Aplikasi Android → {$website}/download-app

=== BERITA TERBARU ===
{$latestNews}

=== PENGUMUMAN AKTIF ===
{$announcements}

=== ATURAN ===
- Jika tidak tahu, arahkan warga menghubungi kantor desa di {$phone} / {$email}.
- Jangan mengarang data penduduk, nomor tiket, atau fakta yang tidak ada di konteks.
- Jawaban singkat, jelas, pakai bullet jika perlu.
CONTEXT;
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message'           => ['required', 'string', 'max:1000'],
            'history'           => ['nullable', 'array', 'max:10'],
            'history.*.role'    => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $userMessage = trim($request->string('message'));
        $history     = $request->input('history', []);

        $apiKey   = config('services.groq.key');
        $baseUrl  = config('services.groq.base_url', 'https://api.groq.com/openai/v1');
        $model     = config('services.groq.model_primary', 'openai/gpt-oss-120b');
        $secondary = config('services.groq.model_secondary', 'qwen/qwen3-27b');
        $secondary = config('services.groq.model_secondary', 'qwen/qwen3-27b');
        $fallback  = config('services.groq.model_fallback', 'openai/gpt-oss-20b');

        if (empty($apiKey)) {
            return response()->json(['ok' => false, 'message' => 'Layanan AI belum dikonfigurasi.'], 503);
        }

        $messages = [['role' => 'system', 'content' => $this->buildVillageContext()]];

        foreach (array_slice($history, -6) as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => 0.6,
            'max_tokens'  => 512,
        ];

        $clean = function (string $text): string {
            return trim((string) preg_replace('/<think>.*?<\/think>/s', '', $text));
        };

        try {
            $response = Http::acceptJson()->withToken($apiKey)->timeout(30)
                ->post("{$baseUrl}/chat/completions", $payload);

            if ($response->successful()) {
                return response()->json([
                    'ok'      => true,
                    'message' => $clean((string) data_get($response->json(), 'choices.0.message.content', '')),
                ]);
            }

            // Secondary model
            $payload['model'] = $secondary;
            $response = Http::acceptJson()->withToken($apiKey)->timeout(30)
                ->post("{$baseUrl}/chat/completions", $payload);

            if ($response->successful()) {
                return response()->json([
                    'ok'      => true,
                    'message' => $clean((string) data_get($response->json(), 'choices.0.message.content', '')),
                ]);
            }

            // Fallback model (third tier)
            $payload['model'] = $fallback;
            $response = Http::acceptJson()->withToken($apiKey)->timeout(30)
                ->post("{$baseUrl}/chat/completions", $payload);

            if ($response->successful()) {
                return response()->json([
                    'ok'      => true,
                    'message' => $clean((string) data_get($response->json(), 'choices.0.message.content', '')),
                ]);
            }

            return response()->json(['ok' => false, 'message' => 'Layanan AI sedang sibuk. Coba lagi sesaat.'], 503);

        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Terjadi kesalahan layanan AI.'], 500);
        }
    }
}
