<?php

namespace Tests\Unit;

use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudinaryServiceTest extends TestCase
{
    public function test_it_builds_a_signed_delivery_url_for_cloudinary_assets(): void
    {
        config([
            'cloudinary.enabled' => true,
            'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key',
            'cloudinary.api_secret' => 'secret',
        ]);

        $service = new CloudinaryService();
        $url = 'https://res.cloudinary.com/demo/raw/upload/v1743416295/sid/archives/surat/test-file.pdf';
        $payload = 'v1743416295/sid/archives/surat/test-file.pdf';
        $signature = $this->shortSignature($payload, 'secret');

        $this->assertSame(
            'https://res.cloudinary.com/demo/raw/upload/s--' . $signature . '--/v1743416295/sid/archives/surat/test-file.pdf',
            $service->deliveryUrl($url)
        );
    }

    public function test_url_reachable_checks_the_signed_cloudinary_url(): void
    {
        config([
            'cloudinary.enabled' => true,
            'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key',
            'cloudinary.api_secret' => 'secret',
        ]);

        $service = new CloudinaryService();
        $url = 'https://res.cloudinary.com/demo/raw/upload/v1743416295/sid/archives/surat/test-file.pdf';
        $signedUrl = $service->deliveryUrl($url);

        Http::fake(function ($request) use ($signedUrl) {
            if ($request->method() === 'HEAD' && $request->url() === $signedUrl) {
                return Http::response('', 200);
            }

            return Http::response('', 404);
        });

        $this->assertTrue($service->urlReachable($url));

        Http::assertSent(fn ($request) => $request->method() === 'HEAD' && $request->url() === $signedUrl);
    }

    private function shortSignature(string $payload, string $secret): string
    {
        $digest = sha1($payload . $secret, true);
        $encoded = rtrim(strtr(base64_encode($digest), '+/', '-_'), '=');

        return substr($encoded, 0, 8);
    }
}
