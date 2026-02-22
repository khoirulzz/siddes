<?php

return [
    'writer' => [
        'enabled' => (bool) env('AI_WRITER_ENABLED', true),
        'provider' => 'openrouter',
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'api_key' => env('OPENROUTER_API_KEY'),
        'primary_model' => env('OPENROUTER_MODEL_PRIMARY', 'meta-llama/llama-3.1-8b-instruct:free'),
        'fallback_model' => env('OPENROUTER_MODEL_FALLBACK', 'google/gemma-2-9b-it:free'),
        'timeout_seconds' => (int) env('OPENROUTER_TIMEOUT_SECONDS', 25),
        'temperature' => (float) env('OPENROUTER_TEMPERATURE', 0.65),
        'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 1400),
        'http_referer' => env('OPENROUTER_HTTP_REFERER', env('APP_URL', 'http://localhost')),
        'x_title' => env('OPENROUTER_X_TITLE', env('APP_NAME', 'WebDes')),
    ],

    'prompts' => [
        'news' => [
            'system' => <<<'PROMPT'
Anda adalah editor resmi portal Desa Lambanggelun.
Gunakan bahasa Indonesia baku, jelas, netral, tidak kaku seperti AI dan tidak berlebihan, mengalir dan mudah dipahami warga.
Hindari data sensitif, hoaks, fitnah, serta klaim tanpa dasar. Gunakan hanya berdasar informasi yang tersedia dan umum diketahui.
Balasan wajib satu objek JSON valid saja tanpa markdown atau teks tambahan. langsung tuliskan berita tanpa template pengantar khas AI.
PROMPT,
            'instruction' => <<<'PROMPT'
Hasilkan JSON dengan struktur:
{
  "title": "string, maksimal 120 karakter",
  "excerpt": "string ringkas 1-2 kalimat",
  "content": "string, paragraf lengkap siap tayang dengan panjang 300 - 400 kata dan ada spacing enter per paragraf"
}
PROMPT,
        ],
        'announcement' => [
            'system' => <<<'PROMPT'
Anda adalah penyusun pengumuman resmi Desa Lambanggelun.
Tulisan harus lugas, ringkas, dan memberi pengumuman informasi yang jelas untuk warga.
Balasan wajib satu objek JSON valid saja tanpa markdown atau teks tambahan.
PROMPT,
            'instruction' => <<<'PROMPT'
Hasilkan JSON dengan struktur:
{
  "title": "string, maksimal 120 karakter",
  "content": "string, isi pengumuman resmi dengan poin waktu/tempat/syarat bila tersedia dengan panjang 200 kata"
}
PROMPT,
        ],
    ],
];

