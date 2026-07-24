<?php

return [
    'writer' => [
        'enabled' => (bool) env('AI_WRITER_ENABLED', true),
        'providers' => ['groq'],
    ],

    'providers' => [
        'groq' => [
            'enabled' => true,
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key' => env('GROQ_API_KEY'),
            'primary_model' => env('GROQ_MODEL_PRIMARY', 'openai/gpt-oss-120b'),
            'fallback_model' => env('GROQ_MODEL_FALLBACK', 'openai/gpt-oss-20b'),
            'timeout_seconds' => 30,
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ],
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
