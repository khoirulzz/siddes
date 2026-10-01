<?php

namespace Tests\Unit;

use App\Models\Gallery;
use App\Support\PublicMedia;
use Tests\TestCase;

class PublicMediaCdnTest extends TestCase
{
    public function test_public_cloudinary_images_use_the_branded_domain_without_changing_stored_urls(): void
    {
        config(['cloudinary.delivery_base_url' => 'https://cdn.desalambanggelun.id']);

        $original = 'https://res.cloudinary.com/dcf6mkq3q/image/upload/f_auto/v123/photo.jpg?x=1';
        $branded = 'https://cdn.desalambanggelun.id/dcf6mkq3q/image/upload/f_auto/v123/photo.jpg?x=1';
        $gallery = new Gallery(['image_url' => $original]);

        $this->assertSame($branded, PublicMedia::toUrl($original));
        $this->assertSame($branded, $gallery->image_url);
        $this->assertSame($original, $gallery->getAttributes()['image_url']);
    }

    public function test_private_media_other_hosts_and_disabled_cdn_keep_their_original_urls(): void
    {
        config(['cloudinary.delivery_base_url' => 'https://cdn.desalambanggelun.id']);

        $private = 'https://res.cloudinary.com/dcf6mkq3q/raw/authenticated/v1/akta.pdf';
        $other = 'https://example.com/photo.jpg';
        $otherCloud = 'https://res.cloudinary.com/another/image/upload/v1/photo.jpg';
        $this->assertSame($private, PublicMedia::toUrl($private));
        $this->assertSame($other, PublicMedia::toUrl($other));
        $this->assertSame($otherCloud, PublicMedia::toUrl($otherCloud));
        $this->assertSame('/assets/images/logo_pekalongan.svg', PublicMedia::displayUrl('/assets/images/logo_pekalongan.svg'));
        $this->assertSame(
            'https://cdn.desalambanggelun.id/dcf6mkq3q/raw/upload/v1/public.pdf',
            PublicMedia::toUrl('https://res.cloudinary.com/dcf6mkq3q/raw/upload/v1/public.pdf')
        );
        $this->assertSame('https://cdn.desalambanggelun.id/dzrca841f/image/upload/v1/legacy.webp',
            PublicMedia::toUrl('dzrca841f/image/upload/v1/legacy.webp'));

        config(['cloudinary.delivery_base_url' => '']);
        $image = 'https://res.cloudinary.com/dcf6mkq3q/image/upload/v1/photo.webp';
        $this->assertSame($image, PublicMedia::toUrl($image));
    }
}
