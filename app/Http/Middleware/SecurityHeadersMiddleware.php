<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type') ?? '';
        if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/json')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        // CSP tetap membatasi sumber aset; img-src hanya menambahkan host media yang dipakai website.
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net data:; " .
                "img-src 'self' data: blob: " .
                "https://res.cloudinary.com https://cdn.desalambanggelun.id " .
                "https://upload.wikimedia.org " .
                "https://picsum.photos https://fastly.picsum.photos " .
                "https://desalambanggelun.web.id https://desalambanggelun.id " .
                "https://lh3.googleusercontent.com https://images.unsplash.com; " .
                "connect-src 'self' https://api.groq.com; " .
                "frame-src 'self' https://www.google.com https://maps.google.com; " .
                "frame-ancestors 'self'; " .
                "object-src 'none'; " .
                "base-uri 'self';"
            );
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
