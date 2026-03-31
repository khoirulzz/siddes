<?php

namespace Tests\Unit;

use App\Services\AI\AiWriterService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiWriterServiceTest extends TestCase
{
    public function test_it_uses_gemini_as_the_primary_provider_when_available(): void
    {
        config([
            'ai.writer.enabled' => true,
            'ai.writer.providers' => ['gemini', 'openrouter'],
            'ai.providers.gemini' => [
                'enabled' => true,
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'api_key' => 'gemini-key',
                'primary_model' => 'gemini-3-flash-preview',
                'fallback_model' => '',
                'timeout_seconds' => 25,
                'temperature' => 0.65,
                'max_tokens' => 1400,
            ],
            'ai.providers.openrouter' => [
                'enabled' => true,
                'base_url' => 'https://openrouter.ai/api/v1',
                'api_key' => 'openrouter-key',
                'primary_model' => 'openrouter-primary',
                'fallback_model' => 'openrouter-fallback',
                'timeout_seconds' => 25,
                'temperature' => 0.65,
                'max_tokens' => 1400,
                'http_referer' => 'http://localhost',
                'x_title' => 'WebDes',
            ],
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'title' => 'Judul Berita',
                                'excerpt' => 'Ringkasan singkat.',
                                'content' => 'Isi berita lengkap.',
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $service = new AiWriterService();
        $result = $service->generate('news', [
            'topic' => 'Panen Raya',
            'brief' => 'Deskripsi singkat kegiatan.',
            'tone' => 'informatif',
            'length' => 'medium',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('gemini-3-flash-preview', $result['model']);

        Http::assertSentCount(1);
    }

    public function test_it_falls_back_to_openrouter_when_gemini_fails(): void
    {
        config([
            'ai.writer.enabled' => true,
            'ai.writer.providers' => ['gemini', 'openrouter'],
            'ai.providers.gemini' => [
                'enabled' => true,
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
                'api_key' => 'gemini-key',
                'primary_model' => 'gemini-3-flash-preview',
                'fallback_model' => '',
                'timeout_seconds' => 25,
                'temperature' => 0.65,
                'max_tokens' => 1400,
            ],
            'ai.providers.openrouter' => [
                'enabled' => true,
                'base_url' => 'https://openrouter.ai/api/v1',
                'api_key' => 'openrouter-key',
                'primary_model' => 'openrouter-primary',
                'fallback_model' => 'openrouter-fallback',
                'timeout_seconds' => 25,
                'temperature' => 0.65,
                'max_tokens' => 1400,
                'http_referer' => 'http://localhost',
                'x_title' => 'WebDes',
            ],
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent' => Http::response([
                'error' => ['message' => 'Model Gemini gagal.'],
            ], 500),
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Judul Pengumuman',
                            'content' => 'Isi pengumuman lengkap.',
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200),
        ]);

        $service = new AiWriterService();
        $result = $service->generate('announcement', [
            'topic' => 'Jadwal Posyandu',
            'brief' => 'Informasi kegiatan warga.',
            'tone' => 'formal',
            'length' => 'short',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('openrouter', $result['provider']);
        $this->assertSame('openrouter-primary', $result['model']);

        Http::assertSentCount(2);
    }
}
