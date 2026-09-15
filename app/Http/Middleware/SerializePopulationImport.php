<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SerializePopulationImport
{
    public function handle(Request $request, Closure $next): Response
    {
        // Per-instance lock: no Redis dependency, and other website pages stay unlocked.
        // A crashed worker leaves a bounded lease; normal responses always release it.
        $lock = Cache::store('file')->lock('population-import-processing', 600);
        if (! $lock->get()) {
            return response()->json([
                'message' => 'Pemeriksaan atau import lain masih berlangsung di server. Tunggu hingga selesai sebelum mencoba kembali. Jika proses sebelumnya terputus, pengaman akan terbuka otomatis paling lambat 10 menit.',
            ], 429, ['Retry-After' => '30']);
        }

        try {
            return $next($request);
        } finally {
            $lock->release();
        }
    }
}
