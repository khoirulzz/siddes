<?php

namespace App\Services\AI;

use App\Models\AiGeneration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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
     *     model?:string,
     *     provider?:string
     * }
     */
    public function generate(string $feature, array $input, ?int $userId = null): array
    {
        $writerConfig = config('ai.writer', []);
        $enabled = (bool) ($writerConfig['enabled'] ?? false);
        $providerOrder = array_values(array_unique(array_filter(
            (array) ($writerConfig['providers'] ?? ['gemini', 'openrouter']),
            static fn ($provider) => is_string($provider) && trim($provider) !== ''
        )));

        $fallbackProvider = $providerOrder[0] ?? 'gemini';

        if (! $enabled) {
            return $this->fail(
                $feature,
                $input,
                $userId,
                $fallbackProvider,
                '',
                '',
                503,
                'Layanan AI dinonaktifkan pada konfigurasi aplikasi.',
                'Layanan AI belum aktif. Silakan lanjutkan penulisan manual.'
            );
        }

        [$systemPrompt, $userPrompt] = $this->buildPrompts($feature, $input);
        $requestPayload = [
            'feature' => $feature,
            'input' => $input,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $providers = $this->configuredProviders($providerOrder);
        if ($providers === []) {
            return $this->fail(
                $feature,
                $requestPayload,
                $userId,
                $fallbackProvider,
                '',
                '',
                503,
                'Konfigurasi API key Gemini maupun OpenRouter belum tersedia.',
                'Layanan AI belum aktif. Silakan lanjutkan penulisan manual.'
            );
        }

        $lastStatus = 500;
        $lastError = 'Layanan AI tidak tersedia sementara.';
        $lastProvider = $providers[0]['name'];
        $lastPrimaryModel = $providers[0]['primary_model'];
        $lastFallbackModel = $providers[0]['fallback_model'];

        foreach ($providers as $provider) {
            foreach ($provider['models'] as $model) {
                $lastProvider = $provider['name'];
                $lastPrimaryModel = $provider['primary_model'];
                $lastFallbackModel = $provider['fallback_model'];

                try {
                    $response = $this->performProviderRequest(
                        $provider,
                        $model,
                        $feature,
                        $systemPrompt,
                        $userPrompt
                    );
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
                    $lastError = $this->providerErrorMessage($provider['name'], $response);
                    continue;
                }

                $content = $this->extractMessageContent($provider['name'], $response->json());
                $parsed = $this->parseContent($feature, $content);

                if (! $parsed) {
                    $lastStatus = 422;
                    $lastError = 'AI mengembalikan format tidak valid.';
                    continue;
                }

                $this->logGeneration([
                    'user_id' => $userId,
                    'feature' => $feature,
                    'provider' => $provider['name'],
                    'primary_model' => $provider['primary_model'],
                    'fallback_model' => $provider['fallback_model'] ?: null,
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
                    'provider' => $provider['name'],
                ];
            }
        }

        return $this->fail(
            $feature,
            $requestPayload,
            $userId,
            $lastProvider,
            $lastPrimaryModel,
            $lastFallbackModel,
            $this->statusCodeForClient($lastStatus),
            $lastError,
            $this->friendlyMessage($lastStatus, $lastError)
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
     * @param array<int, string> $providerOrder
     * @return array<int, array{name:string,base_url:string,api_key:string,models:array<int,string>,primary_model:string,fallback_model:string,timeout_seconds:int,temperature:float,max_tokens:int,http_referer:string,x_title:string}>
     */
    private function configuredProviders(array $providerOrder): array
    {
        $configured = [];

        foreach ($providerOrder as $providerName) {
            $providerConfig = config("ai.providers.{$providerName}", []);
            $enabled = (bool) ($providerConfig['enabled'] ?? false);
            $apiKey = trim((string) ($providerConfig['api_key'] ?? ''));
            $primaryModel = trim((string) ($providerConfig['primary_model'] ?? ''));
            $fallbackModel = trim((string) ($providerConfig['fallback_model'] ?? ''));
            $models = array_values(array_unique(array_filter([$primaryModel, $fallbackModel])));

            if (! $enabled || $apiKey === '' || $models === []) {
                continue;
            }

            $configured[] = [
                'name' => $providerName,
                'base_url' => rtrim((string) ($providerConfig['base_url'] ?? ''), '/'),
                'api_key' => $apiKey,
                'models' => $models,
                'primary_model' => $primaryModel,
                'fallback_model' => $fallbackModel,
                'timeout_seconds' => max(8, (int) ($providerConfig['timeout_seconds'] ?? 25)),
                'temperature' => (float) ($providerConfig['temperature'] ?? 0.65),
                'max_tokens' => max(300, (int) ($providerConfig['max_tokens'] ?? 1400)),
                'http_referer' => trim((string) ($providerConfig['http_referer'] ?? '')),
                'x_title' => trim((string) ($providerConfig['x_title'] ?? '')),
            ];
        }

        return $configured;
    }

    /**
     * @param array{name:string,base_url:string,api_key:string,models:array<int,string>,primary_model:string,fallback_model:string,timeout_seconds:int,temperature:float,max_tokens:int,http_referer:string,x_title:string} $provider
     */
    private function performProviderRequest(
        array $provider,
        string $model,
        string $feature,
        string $systemPrompt,
        string $userPrompt
    ): Response {
        return match ($provider['name']) {
            'gemini' => $this->requestGemini($provider, $model, $feature, $systemPrompt, $userPrompt),
            default => $this->requestOpenRouter($provider, $model, $systemPrompt, $userPrompt),
        };
    }

    /**
     * @param array{name:string,base_url:string,api_key:string,models:array<int,string>,primary_model:string,fallback_model:string,timeout_seconds:int,temperature:float,max_tokens:int,http_referer:string,x_title:string} $provider
     */
    private function requestGemini(
        array $provider,
        string $model,
        string $feature,
        string $systemPrompt,
        string $userPrompt
    ): Response {
        return Http::acceptJson()
            ->withHeaders([
                'x-goog-api-key' => $provider['api_key'],
            ])
            ->timeout($provider['timeout_seconds'])
            ->post("{$provider['base_url']}/models/{$model}:generateContent", [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => $provider['temperature'],
                    'maxOutputTokens' => $provider['max_tokens'],
                    'responseMimeType' => 'application/json',
                    'responseJsonSchema' => $this->responseSchemaForFeature($feature),
                ],
            ]);
    }

    /**
     * @param array{name:string,base_url:string,api_key:string,models:array<int,string>,primary_model:string,fallback_model:string,timeout_seconds:int,temperature:float,max_tokens:int,http_referer:string,x_title:string} $provider
     */
    private function requestOpenRouter(
        array $provider,
        string $model,
        string $systemPrompt,
        string $userPrompt
    ): Response {
        return Http::acceptJson()
            ->withToken($provider['api_key'])
            ->withHeaders([
                'HTTP-Referer' => $provider['http_referer'],
                'X-Title' => $provider['x_title'],
            ])
            ->timeout($provider['timeout_seconds'])
            ->post("{$provider['base_url']}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => $provider['temperature'],
                'max_tokens' => $provider['max_tokens'],
            ]);
    }

    /**
     * @param array<string, mixed> $responseJson
     */
    private function extractMessageContent(string $provider, array $responseJson): string
    {
        if ($provider === 'gemini') {
            $parts = data_get($responseJson, 'candidates.0.content.parts', []);
            if (! is_array($parts)) {
                return '';
            }

            $segments = [];
            foreach ($parts as $part) {
                if (is_array($part) && isset($part['text'])) {
                    $segments[] = (string) $part['text'];
                }
            }

            return trim(implode("\n", $segments));
        }

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
     * @return array<string, mixed>
     */
    private function responseSchemaForFeature(string $feature): array
    {
        $properties = [
            'title' => ['type' => 'string'],
            'content' => ['type' => 'string'],
        ];
        $required = ['title', 'content'];

        if ($feature === 'news') {
            $properties['excerpt'] = ['type' => 'string'];
            $required[] = 'excerpt';
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    private function providerErrorMessage(string $provider, Response $response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            $error = (string) data_get($json, 'error.message', '');
            if ($error !== '') {
                return $error;
            }

            if ($provider === 'gemini') {
                $message = (string) data_get($json, 'promptFeedback.blockReasonMessage', '');
                if ($message !== '') {
                    return $message;
                }
            }
        }

        return trim($response->body()) ?: 'Layanan AI mengembalikan respons gagal.';
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

        if ($status === 504) {
            return 504;
        }

        if ($status >= 500) {
            return 503;
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
        string $provider,
        string $primaryModel,
        string $fallbackModel,
        int $status,
        string $error,
        string $friendly
    ): array {
        $this->logGeneration([
            'user_id' => $userId,
            'feature' => $feature,
            'provider' => $provider,
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
