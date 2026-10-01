<?php

namespace Tests\Unit;

use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudinaryServiceTest extends TestCase
{
    public function test_it_signs_a_short_lived_private_pdf_cdn_url(): void
    {
        config([
            'cloudinary.enabled' => true,
            'cloudinary.cloud_name' => 'dcf6mkq3q',
            'cloudinary.api_key' => 'key',
            'cloudinary.api_secret' => 'secret',
            'cloudinary.delivery_base_url' => 'https://cdn.desalambanggelun.id',
        ]);

        $url = (new CloudinaryService())->privatePdfCdnUrl('resident-asset-1');
        $this->assertNotNull($url);
        $this->assertSame('cdn.desalambanggelun.id', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/private/pdf/resident-asset-1', parse_url($url, PHP_URL_PATH));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertGreaterThan(time(), (int) $query['exp']);
        $this->assertLessThanOrEqual(time() + 300, (int) $query['exp']);
        $this->assertSame(
            hash_hmac('sha256', "siddes-private-pdf:v1\ndcf6mkq3q\nresident-asset-1\n" . $query['exp'], 'secret'),
            $query['sig']
        );
    }

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

    public function test_authenticated_raw_asset_deletion_signs_public_id_and_delivery_type(): void
    {
        config([
            'cloudinary.enabled' => true,
            'cloudinary.cloud_name' => 'demo',
            'cloudinary.api_key' => 'key',
            'cloudinary.api_secret' => 'secret',
        ]);
        Http::fake(['*/raw/destroy' => Http::response(['result' => 'ok'])]);

        $this->assertTrue((new CloudinaryService())->destroyRawAsset('sid/population-documents/akta.pdf'));

        Http::assertSent(function ($request): bool {
            $params = [
                'public_id' => $request['public_id'],
                'timestamp' => $request['timestamp'],
                'type' => $request['type'],
            ];

            return $request->url() === 'https://api.cloudinary.com/v1_1/demo/raw/destroy'
                && $request['public_id'] === 'sid/population-documents/akta.pdf'
                && $request['type'] === 'authenticated'
                && $request['signature'] === sha1(
                    'public_id='.$params['public_id'].'&timestamp='.$params['timestamp'].'&type='.$params['type'].'secret'
                );
        });
    }

    private function shortSignature(string $payload, string $secret): string
    {
        $digest = sha1($payload . $secret, true);
        $encoded = rtrim(strtr(base64_encode($digest), '+/', '-_'), '=');

        return substr($encoded, 0, 8);
    }
}
