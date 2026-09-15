<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SerializePbbImport
{
    public function handle(Request $request, Closure $next)
    {
        $lock = Cache::store('file')->lock('pbb-import-lock', 600);

        if (! $lock->get()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Proses import data PBB lain sedang berjalan. Silakan tunggu beberapa saat lalu coba lagi.',
                ], 429);
            }

            return redirect()->back()->withErrors([
                'file' => 'Proses import data PBB lain sedang berjalan. Silakan tunggu beberapa saat lalu coba lagi.',
            ]);
        }

        try {
            return $next($request);
        } finally {
            $lock->release();
        }
    }
}
