<?php

namespace App\Providers;

use App\Models\WebsiteSetting;
use App\Support\PublicMedia;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    private const VILLAGE_CONFIG_CACHE_KEY = 'website-settings.village-overrides';

    private const VILLAGE_SETTING_MAP = [
        'village_name' => 'village.name',
        'village_district' => 'village.district',
        'village_address' => 'village.address',
        'village_phone' => 'village.phone',
        'village_email' => 'village.email',
        'village_instagram_url' => 'village.instagram_url',
        'village_facebook_url' => 'village.facebook_url',
        'village_map_link_url' => 'village.map_link_url',
        'village_hero_image_url' => 'village.hero_image_url',
        'village_profile_hero_image_url' => 'village.profile_hero_image_url',
        'village_head_name' => 'village.head_name',
        'village_head_position' => 'village.head_position',
        'village_head_photo_url' => 'village.head_photo_url',
        'village_profile_gallery_images' => 'village.profile_gallery_images',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->applyVillageConfigOverrides();

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));
            $identity = $email !== '' ? $email : 'guest';

            return Limit::perMinute(6)->by($identity . '|' . (string) $request->ip());
        });

        RateLimiter::for('service-lookup', function (Request $request) {
            return Limit::perMinute(80)->by((string) $request->ip());
        });

        RateLimiter::for('service-submit', function (Request $request) {
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(20)->by($ip),
                Limit::perHour(120)->by($ip),
            ];
        });
    }

    private function applyVillageConfigOverrides(): void
    {
        try {
            if (! Schema::hasTable('website_settings')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $keys = array_keys(self::VILLAGE_SETTING_MAP);
        $overrides = Cache::remember(self::VILLAGE_CONFIG_CACHE_KEY, now()->addMinutes(10), static function () use ($keys): array {
            return WebsiteSetting::query()
                ->whereIn('key', $keys)
                ->pluck('value', 'key')
                ->all();
        });

        foreach (self::VILLAGE_SETTING_MAP as $settingKey => $configKey) {
            $rawValue = trim((string) ($overrides[$settingKey] ?? ''));
            if ($rawValue === '') {
                continue;
            }

            config([$configKey => $this->castSettingValue($settingKey, $rawValue)]);
        }
    }

    private function castSettingValue(string $settingKey, string $rawValue): mixed
    {
        if ($settingKey === 'village_profile_gallery_images') {
            $decoded = json_decode($rawValue, true);
            if (! is_array($decoded)) {
                return config('village.profile_gallery_images', []);
            }

            return array_values(array_filter(
                array_map(static fn ($item) => PublicMedia::toUrl((string) $item) ?? (string) $item, $decoded),
                static fn (string $item) => $item !== ''
            ));
        }

        if (in_array($settingKey, ['village_hero_image_url', 'village_profile_hero_image_url', 'village_head_photo_url'], true)) {
            return PublicMedia::toUrl($rawValue) ?? $rawValue;
        }

        return $rawValue;
    }
}
