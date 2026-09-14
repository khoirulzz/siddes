<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
Jawab pertanyaan warga dengan ramah, informatif, dan jelas dalam Bahasa Indonesia.
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
- Jawaban jelas dan terstruktur, gunakan poin/bullet jika perlu.
CONTEXT;
    }

    public function sendMessage(Request $request): JsonResponse|StreamedResponse
    {
        $request->validate([
            'message'           => ['required', 'string', 'max:1000'],
            'history'           => ['nullable', 'array', 'max:10'],
            'history.*.role'    => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $userMessage = trim($request->string('message'));
        $history     = $request->input('history', []);

        $apiKey    = config('services.groq.key');
        $baseUrl   = config('services.groq.base_url', 'https://api.groq.com/openai/v1');
        $primary   = config('services.groq.model_primary', 'openai/gpt-oss-120b');
        $secondary = config('services.groq.model_secondary', 'qwen/qwen3-27b');
        $fallback  = config('services.groq.model_fallback', 'openai/gpt-oss-20b');
        $maxTokens = (int) config('services.groq.max_tokens', 2048);

        if (empty($apiKey)) {
            return response()->json(['ok' => false, 'message' => 'Layanan AI belum dikonfigurasi.'], 503);
        }

        $messages = [['role' => 'system', 'content' => $this->buildVillageContext()]];

        foreach (array_slice($history, -6) as $h) {
            $messages[] = ['role' => $h['role'], 'content' => $h['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $modelsToTry = array_values(array_unique(array_filter([$primary, $secondary, $fallback])));

        return response()->stream(function () use ($apiKey, $baseUrl, $modelsToTry, $messages, $maxTokens) {
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $success = false;

            foreach ($modelsToTry as $currentModel) {
                $payload = [
                    'model'       => $currentModel,
                    'messages'    => $messages,
                    'temperature' => 0.6,
                    'max_tokens'  => $maxTokens,
                    'stream'      => true,
                ];

                $ch = curl_init("{$baseUrl}/chat/completions");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: text/event-stream',
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);

                $httpCode = 0;
                $buffer = '';
                $hasEmittedData = false;
                $inThinkBlock = false;

                curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$httpCode) {
                    if (preg_match('/^HTTP\/\d(?:\.\d)?\s+(\d+)/', $headerLine, $m)) {
                        $httpCode = (int) $m[1];
                    }
                    return strlen($headerLine);
                });

                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$buffer, &$hasEmittedData, &$inThinkBlock, &$httpCode) {
                    if ($httpCode !== 0 && $httpCode !== 200) {
                        $buffer .= $chunk;
                        return strlen($chunk);
                    }

                    $buffer .= $chunk;

                    while (($pos = strpos($buffer, "\n")) !== false) {
                        $line = trim(substr($buffer, 0, $pos));
                        $buffer = substr($buffer, $pos + 1);

                        if (str_starts_with($line, 'data: ')) {
                            $jsonStr = trim(substr($line, 6));

                            if ($jsonStr === '[DONE]') {
                                echo "data: [DONE]\n\n";
                                if (ob_get_level() > 0) ob_flush();
                                flush();
                                $hasEmittedData = true;
                                break;
                            }

                            $decoded = json_decode($jsonStr, true);
                            $delta = (string) data_get($decoded, 'choices.0.delta.content', '');

                            if ($delta !== '') {
                                if (str_contains($delta, '<think>')) {
                                    $inThinkBlock = true;
                                    $parts = explode('<think>', $delta, 2);
                                    $delta = $parts[0];
                                }

                                if ($inThinkBlock) {
                                    if (str_contains($delta, '</think>')) {
                                        $inThinkBlock = false;
                                        $parts = explode('</think>', $delta, 2);
                                        $delta = $parts[1];
                                    } else {
                                        $delta = '';
                                    }
                                }

                                if ($delta !== '') {
                                    $hasEmittedData = true;
                                    echo "data: " . json_encode(['content' => $delta], JSON_UNESCAPED_UNICODE) . "\n\n";
                                    if (ob_get_level() > 0) ob_flush();
                                    flush();
                                }
                            }
                        }
                    }

                    return strlen($chunk);
                });

                curl_exec($ch);
                curl_close($ch);

                if ($hasEmittedData) {
                    $success = true;
                    break;
                }
            }

            if (! $success) {
                echo "data: " . json_encode(['error' => 'Layanan AI sedang sibuk. Silakan coba lagi.'], JSON_UNESCAPED_UNICODE) . "\n\n";
                echo "data: [DONE]\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
}
