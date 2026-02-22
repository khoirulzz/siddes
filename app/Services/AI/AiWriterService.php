<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiWriterService
{
    /**
     * @param array<string, mixed> $input
     * @return array{
     *     ok:bool,
     *     message:string,
     *     status:int,
     *     data?:array<string, string>,
     *     model?:string
     * }
     */
    public function generate(string $feature, array $input, ?int $userId = null): array
    {
        $writerConfig = config('ai.writer', []);
        $apiKey = trim((string) ($writerConfig['api_key'] ?? ''));
        $enabled = (bool) ($writerConfig['enabled'] ?? false);

        if (! $enabled || $apiKey === '') {
            return $this->fail(
                $feature,
                $input,
                $userId,
                (string) ($writerConfig['primary_model'] ?? ''),
                (string) ($writerConfig['fallback_model'] ?? ''),
                503,
                'Konfigurasi API key OpenRouter belum tersedia.',
                'Layanan AI belum aktif. Silakan lanjutkan penulisan manual.'
            );
        }

        [$systemPrompt, $userPrompt] = $this->buildPrompts($feature, $input);
        $baseUrl = rtrim((string) ($writerConfig['base_url'] ?? 'https://openrouter.ai/api/v1'), '/');
        $primaryModel = trim((string) ($writerConfig['primary_model'] ?? ''));
        $fallbackModel = trim((string) ($writerConfig['fallback_model'] ?? ''));
        $timeoutSeconds = max(8, (int) ($writerConfig['timeout_seconds'] ?? 25));
        $temperature = (float) ($writerConfig['temperature'] ?? 0.65);
        $maxTokens = max(300, (int) ($writerConfig['max_tokens'] ?? 1400));
        $models = array_values(array_unique(array_filter([$primaryModel, $fallbackModel])));

        if ($models === []) {
            return $this->fail(
                $feature,
                $input,
                $userId,
                $primaryModel,
                $fallbackModel,
                422,
                'Model AI belum diatur.',
                'Model AI belum dikonfigurasi. Silakan lanjutkan penulisan manual.'
            );
        }

        $requestPayload = [
            'feature' => $feature,
            'input' => $input,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $deadline = microtime(true) + $timeoutSeconds;
        $lastStatus = 500;
        $lastError = 'Layanan AI tidak tersedia sementara.';

        foreach ($models as $model) {
            $remaining = (int) floor($deadline - microtime(true));
            if ($remaining <= 2) {
                break;
            }

            try {
                $response = Http::acceptJson()
                    ->withToken($apiKey)
                    ->withHeaders([
                        'HTTP-Referer' => (string) ($writerConfig['http_referer'] ?? ''),
                        'X-Title' => (string) ($writerConfig['x_title'] ?? ''),
                    ])
                    ->timeout($remaining)
                    ->post("{$baseUrl}/chat/completions", [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                    ]);
            } catch (ConnectionException $exception) {
                $lastStatus = 504;
                $lastError = $exception->getMessage();
                continue;
            } catch (Throwable $exception) {
                $lastStatus = 500;
                $lastError = $exception->getMessage();
                continue;
            }

            if (! $response->successful()) {
                $lastStatus = $response->status();
                $lastError = (string) data_get($response->json(), 'error.message', $response->body());
                continue;
            }

            $content = $this->extractMessageContent($response->json());
            $parsed = $this->parseContent($feature, $content);

            if (! $parsed) {
                $lastStatus = 422;
                $lastError = 'AI mengembalikan format tidak valid.';
                continue;
            }

            $this->logGeneration([
                'user_id' => $userId,
                'feature' => $feature,
                'provider' => 'openrouter',
                'primary_model' => $primaryModel,
                'fallback_model' => $fallbackModel ?: null,
                'used_model' => $model,
                'request_payload' => $requestPayload,
                'response_payload' => [
                    'raw' => $response->json(),
                    'parsed' => $parsed,
                ],
                'status' => 'success',
                'error_message' => null,
            ]);

            return [
                'ok' => true,
                'message' => $feature === 'news'
                    ? 'Draft berita berhasil dibuat. Silakan review sebelum tayang.'
                    : 'Draft pengumuman berhasil dibuat. Silakan review sebelum tayang.',
                'status' => 200,
                'data' => $parsed,
                'model' => $model,
            ];
        }

        $friendly = $this->friendlyMessage($lastStatus, $lastError);

        return $this->fail(
            $feature,
            $requestPayload,
            $userId,
            $primaryModel,
            $fallbackModel,
            $this->statusCodeForClient($lastStatus),
            $lastError,
            $friendly
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0:string,1:string}
     */
    private function buildPrompts(string $feature, array $input): array
    {
        $promptConfig = config("ai.prompts.{$feature}", []);
        $systemPrompt = trim((string) ($promptConfig['system'] ?? ''));
        $instruction = trim((string) ($promptConfig['instruction'] ?? ''));

        $topic = trim((string) ($input['topic'] ?? ''));
        $brief = trim((string) ($input['brief'] ?? ''));
        $tone = trim((string) ($input['tone'] ?? 'informatif'));
        $length = trim((string) ($input['length'] ?? 'medium'));
        $keywords = trim((string) ($input['keywords'] ?? ''));

        $lengthLabel = match ($length) {
            'short' => 'pendek (2-3 paragraf)',
            'long' => 'panjang (6-8 paragraf)',
            default => 'sedang (4-5 paragraf)',
        };

        $userPrompt = implode("\n", array_filter([
            "Topik utama: {$topic}",
            "Informasi/deskripsi operator: {$brief}",
            "Gaya penulisan: {$tone}",
            "Panjang konten: {$lengthLabel}",
            $keywords !== '' ? "Kata kunci penting: {$keywords}" : null,
            $instruction,
        ]));

        return [$systemPrompt, $userPrompt];
    }

    /**
     * @param array<string, mixed> $responseJson
     */
    private function extractMessageContent(array $responseJson): string
    {
        $content = data_get($responseJson, 'choices.0.message.content', '');

        if (is_array($content)) {
            $segments = [];
            foreach ($content as $part) {
                if (is_string($part)) {
                    $segments[] = $part;
                    continue;
                }

                if (is_array($part)) {
                    $segments[] = (string) ($part['text'] ?? '');
                }
            }

            return trim(implode("\n", $segments));
        }

        return trim((string) $content);
    }

    /**
     * @return array<string, string>|null
     */
    private function parseContent(string $feature, string $content): ?array
    {
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            if (! preg_match('/\{.*\}/s', $content, $matches)) {
                return null;
            }

            $decoded = json_decode((string) $matches[0], true);
            if (! is_array($decoded)) {
                return null;
            }
        }

        $title = trim((string) ($decoded['title'] ?? ''));
        $body = trim((string) ($decoded['content'] ?? ''));
        $excerpt = trim((string) ($decoded['excerpt'] ?? ''));

        if ($title === '' || $body === '') {
            return null;
        }

        if ($feature === 'news' && $excerpt === '') {
            $excerpt = Str::limit(strip_tags($body), 180, '...');
        }

        $result = [
            'title' => $title,
            'content' => $body,
        ];

        if ($feature === 'news') {
            $result['excerpt'] = $excerpt;
        }

        return $result;
    }

    private function friendlyMessage(int $status, string $error): string
    {
        $lower = Str::lower($error);

        if ($status === 429) {
            return 'Layanan AI sedang sibuk. Coba lagi beberapa saat atau lanjutkan mode manual.';
        }

        if ($status === 504 || Str::contains($lower, ['timed out', 'timeout', 'curl error 28'])) {
            return 'Permintaan ke AI melebihi batas waktu. Silakan coba lagi atau lanjutkan mode manual.';
        }

        if ($status >= 500) {
            return 'Layanan AI tidak tersedia sementara. Operator tetap bisa menulis manual.';
        }

        if ($status === 422 || Str::contains($lower, ['format', 'json', 'parse'])) {
            return 'AI mengembalikan format tidak valid. Silakan generate ulang atau lanjutkan mode manual.';
        }

        return 'AI belum dapat memproses permintaan ini. Silakan coba ulang atau gunakan mode manual.';
    }

    private function statusCodeForClient(int $status): int
    {
        if ($status === 429) {
            return 429;
        }

        if ($status >= 500) {
            return 503;
        }

        if ($status === 504) {
            return 504;
        }

        return 422;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{
     *     ok:bool,
     *     message:string,
     *     status:int
     * }
     */
    private function fail(
        string $feature,
        array $input,
        ?int $userId,
        string $primaryModel,
        string $fallbackModel,
        int $status,
        string $error,
        string $friendly
    ): array {
        $this->logGeneration([
            'user_id' => $userId,
            'feature' => $feature,
            'provider' => 'openrouter',
            'primary_model' => $primaryModel,
            'fallback_model' => $fallbackModel ?: null,
            'used_model' => null,
            'request_payload' => $input,
            'response_payload' => null,
            'status' => 'failed',
            'error_message' => Str::limit($error, 1000),
        ]);

        return [
            'ok' => false,
            'message' => $friendly,
            'status' => $status,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function logGeneration(array $attributes): void
    {
        try {
            AiGeneration::create($attributes);
        } catch (Throwable) {
            // Logging failure must not break operator flow.
        }
    }
}

