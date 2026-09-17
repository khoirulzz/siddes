<?php

namespace App\Services\Messaging;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MessagingService
{
    public function request(string $method, string $path, array $data = [], bool $connectionAdmin = false): array
    {
        $base = config('messaging.base_url');
        $key = config($connectionAdmin ? 'messaging.admin_key' : 'messaging.operator_key');
        if (! config('messaging.enabled') || ! $base || strlen((string) $key) < 32) {
            throw new MessagingException('Layanan pesan belum dikonfigurasi.');
        }
        if ($connectionAdmin && ! auth()->user()?->isAdmin()) {
            throw new MessagingException('Pengelolaan koneksi hanya untuk admin.', 403);
        }
        $request = Http::acceptJson()->asJson()->withToken($key)
            ->withHeaders(['X-SID-Actor-Id' => (string) auth()->id()])
            ->connectTimeout(5)->timeout($method === 'GET' ? 10 : 15);
        if (isset($data['_idempotency_key'])) {
            $request = $request->withHeaders(['Idempotency-Key' => $data['_idempotency_key']]);
            unset($data['_idempotency_key']);
        }
        try {
            $response = $request->send($method, $base.'/integration/v1/'.$path, [$method === 'GET' ? 'query' : 'json' => $data]);
        } catch (ConnectionException) {
            throw new MessagingException('Layanan pesan belum dapat dijangkau. Data form tetap dipertahankan.', 503, $method !== 'GET');
        }
        if (! $response->successful()) {
            $message = $response->status() >= 500 ? 'Layanan pesan sedang bermasalah. Coba kembali nanti.' : mb_substr((string) $response->json('message', 'Permintaan pesan tidak dapat diproses.'), 0, 500);
            throw new MessagingException($message, $response->status(), $method !== 'GET' && $response->status() >= 500);
        }
        $body = $response->json();
        if (! is_array($body)) throw new MessagingException('Respons layanan pesan tidak valid.', 503, $method !== 'GET');
        return $body;
    }

    public function page(string $resource, array $query = []): MessagingPage
    {
        return MessagingPage::fromArray($this->request('GET', $resource, $query));
    }
}
