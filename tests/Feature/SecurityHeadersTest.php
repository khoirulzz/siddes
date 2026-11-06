<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_html_csp_allows_the_image_hosts_used_by_the_public_site(): void
    {
        $request = Request::create('https://desalambanggelun.id/');
        $response = new Response('<!doctype html><html></html>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);

        $result = (new SecurityHeadersMiddleware())->handle(
            $request,
            static fn () => $response,
        );

        $csp = (string) $result->headers->get('Content-Security-Policy');

        $this->assertNotSame('', $csp);

        foreach ([
            "img-src 'self' data: blob:",
            'https://res.cloudinary.com',
            'https://upload.wikimedia.org',
            'https://picsum.photos',
            'https://fastly.picsum.photos',
            'https://desalambanggelun.web.id',
            'https://desalambanggelun.id',
            'https://lh3.googleusercontent.com',
            'https://images.unsplash.com',
        ] as $source) {
            $this->assertStringContainsString($source, $csp);
        }

        // Pastikan sumber gambar tetap berupa allowlist host, bukan membuka semua HTTPS.
        $this->assertStringNotContainsString("img-src 'self' https:", $csp);

        // Kebijakan keamanan lain tetap dipertahankan.
        $this->assertStringContainsString("object-src 'none';", $csp);
        $this->assertStringContainsString("frame-ancestors 'self';", $csp);
        $this->assertStringContainsString("base-uri 'self';", $csp);
    }

    public function test_non_html_responses_do_not_receive_the_html_csp_header(): void
    {
        $request = Request::create('https://desalambanggelun.id/logo.svg');
        $response = new Response('<svg></svg>', 200, [
            'Content-Type' => 'image/svg+xml',
        ]);

        $result = (new SecurityHeadersMiddleware())->handle(
            $request,
            static fn () => $response,
        );

        $this->assertNull($result->headers->get('Content-Security-Policy'));
    }
}
