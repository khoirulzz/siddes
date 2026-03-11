<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VillageStaff;
use App\Models\WebsiteSetting;
use App\Services\ImageUploadService;
use App\Support\PublicMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class WebsiteSettingController extends Controller
{
    private const VILLAGE_CONFIG_CACHE_KEY = 'website-settings.village-overrides';

    private const INFO_SETTING_KEYS = [
        'village_name',
        'village_district',
        'village_address',
        'village_phone',
        'village_email',
        'village_instagram_url',
        'village_facebook_url',
        'village_map_link_url',
    ];

    private const MEDIA_SETTING_KEYS = [
        'village_hero_image_url',
        'village_profile_hero_image_url',
        'village_profile_gallery_images',
    ];

    private const HEAD_SETTING_KEYS = [
        'village_head_name',
        'village_head_position',
        'village_head_photo_url',
    ];

    public function __construct(private readonly ImageUploadService $imageUploadService)
    {
    }

    public function edit()
    {
        $settings = WebsiteSetting::query()
            ->whereIn('key', [...self::INFO_SETTING_KEYS, ...self::MEDIA_SETTING_KEYS, ...self::HEAD_SETTING_KEYS])
            ->pluck('value', 'key')
            ->all();

        $info = [
            'name' => $this->settingOrConfig($settings, 'village_name', 'village.name'),
            'district' => $this->settingOrConfig($settings, 'village_district', 'village.district'),
            'address' => $this->settingOrConfig($settings, 'village_address', 'village.address'),
            'phone' => $this->settingOrConfig($settings, 'village_phone', 'village.phone'),
            'email' => $this->settingOrConfig($settings, 'village_email', 'village.email'),
            'instagram_url' => $this->settingOrConfig($settings, 'village_instagram_url', 'village.instagram_url'),
            'facebook_url' => $this->settingOrConfig($settings, 'village_facebook_url', 'village.facebook_url'),
            'map_link_url' => $this->settingOrConfig($settings, 'village_map_link_url', 'village.map_link_url'),
        ];

        $heroValue = $settings['village_hero_image_url'] ?? (string) config('village.hero_image_url', '');
        $profileHeroValue = $settings['village_profile_hero_image_url'] ?? (string) config('village.profile_hero_image_url', '');
        $headPhotoValue = $settings['village_head_photo_url'] ?? (string) config('village.head_photo_url', '');
        $gallery = $this->decodeGallery(
            $settings['village_profile_gallery_images'] ?? null,
            config('village.profile_gallery_images', [])
        );

        return view('dashboard.settings.website', [
            'info' => $info,
            'media' => [
                'hero_value' => (string) $heroValue,
                'hero_preview' => PublicMedia::toUrl($heroValue) ?? (string) $heroValue,
                'profile_hero_value' => (string) $profileHeroValue,
                'profile_hero_preview' => PublicMedia::toUrl($profileHeroValue) ?? (string) $profileHeroValue,
                'gallery_value' => implode(PHP_EOL, $gallery),
                'gallery_preview' => array_map(
                    static fn (string $item) => PublicMedia::toUrl($item) ?? $item,
                    $gallery
                ),
            ],
            'head' => [
                'name' => $this->settingOrConfig($settings, 'village_head_name', 'village.head_name'),
                'position' => $this->settingOrConfig($settings, 'village_head_position', 'village.head_position'),
                'photo_value' => (string) $headPhotoValue,
                'photo_preview' => PublicMedia::toUrl((string) $headPhotoValue) ?? (string) $headPhotoValue,
            ],
            'staffMembers' => VillageStaff::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function updateInfo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'district' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'instagram_url' => ['nullable', 'url', 'max:2048'],
            'facebook_url' => ['nullable', 'url', 'max:2048'],
            'map_link_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'email.email' => 'Format email tidak valid.',
            'instagram_url.url' => 'Format URL Instagram tidak valid.',
            'facebook_url.url' => 'Format URL Facebook tidak valid.',
            'map_link_url.url' => 'Format URL Google Maps tidak valid.',
        ]);

        $this->upsertSettings([
            'village_name' => trim($data['name']),
            'village_district' => trim($data['district']),
            'village_address' => trim($data['address']),
            'village_phone' => trim($data['phone']),
            'village_email' => trim($data['email']),
            'village_instagram_url' => trim((string) ($data['instagram_url'] ?? '')),
            'village_facebook_url' => trim((string) ($data['facebook_url'] ?? '')),
            'village_map_link_url' => trim((string) ($data['map_link_url'] ?? '')),
        ]);

        return redirect()->route('dashboard.website-settings.edit')
            ->with('success', 'Informasi desa berhasil diperbarui.');
    }

    public function updateMedia(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hero_image_url' => ['nullable', 'url', 'max:2048'],
            'profile_hero_image_url' => ['nullable', 'url', 'max:2048'],
            'profile_gallery_images' => ['nullable', 'string'],
            'hero_image_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=7000,max_height=7000',
                'max:5120',
            ],
            'profile_hero_image_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=7000,max_height=7000',
                'max:5120',
            ],
        ]);

        $current = WebsiteSetting::query()
            ->whereIn('key', ['village_hero_image_url', 'village_profile_hero_image_url'])
            ->pluck('value', 'key')
            ->all();

        $heroValue = trim((string) ($data['hero_image_url'] ?? ''));
        $profileHeroValue = trim((string) ($data['profile_hero_image_url'] ?? ''));

        if ($request->hasFile('hero_image_file')) {
            $this->deleteStoredMediaIfLocal((string) ($current['village_hero_image_url'] ?? ''));
            $heroValue = $this->imageUploadService->storeOptimized(
                $request->file('hero_image_file'),
                'website/branding',
                2200,
                1600,
                80
            );
        } elseif (
            $heroValue !== ''
            && $heroValue !== (string) ($current['village_hero_image_url'] ?? '')
        ) {
            $this->deleteStoredMediaIfLocal((string) ($current['village_hero_image_url'] ?? ''));
        } elseif ($heroValue === '' && ! empty($current['village_hero_image_url'])) {
            $this->deleteStoredMediaIfLocal((string) $current['village_hero_image_url']);
        }

        if ($request->hasFile('profile_hero_image_file')) {
            $this->deleteStoredMediaIfLocal((string) ($current['village_profile_hero_image_url'] ?? ''));
            $profileHeroValue = $this->imageUploadService->storeOptimized(
                $request->file('profile_hero_image_file'),
                'website/branding',
                2200,
                1600,
                80
            );
        } elseif (
            $profileHeroValue !== ''
            && $profileHeroValue !== (string) ($current['village_profile_hero_image_url'] ?? '')
        ) {
            $this->deleteStoredMediaIfLocal((string) ($current['village_profile_hero_image_url'] ?? ''));
        } elseif ($profileHeroValue === '' && ! empty($current['village_profile_hero_image_url'])) {
            $this->deleteStoredMediaIfLocal((string) $current['village_profile_hero_image_url']);
        }

        $galleryJson = $this->normalizeGalleryAsJson((string) ($data['profile_gallery_images'] ?? ''));

        $this->upsertSettings([
            'village_hero_image_url' => $heroValue,
            'village_profile_hero_image_url' => $profileHeroValue,
            'village_profile_gallery_images' => $galleryJson,
        ]);

        return redirect()->route('dashboard.website-settings.edit')
            ->with('success', 'Media website berhasil diperbarui.');
    }

    public function updateHeadman(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'head_name' => ['required', 'string', 'max:120'],
            'head_position' => ['required', 'string', 'max:160'],
            'head_photo_url' => ['nullable', 'url', 'max:2048'],
            'head_photo_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=5000,max_height=5000',
                'max:4096',
            ],
        ]);

        $currentPhotoValue = (string) (WebsiteSetting::query()
            ->where('key', 'village_head_photo_url')
            ->value('value') ?? '');

        $headPhotoValue = trim((string) ($data['head_photo_url'] ?? ''));
        if ($request->hasFile('head_photo_file')) {
            $this->deleteStoredMediaIfLocal($currentPhotoValue);
            $headPhotoValue = $this->imageUploadService->storeOptimized(
                $request->file('head_photo_file'),
                'website/head',
                1200,
                1200,
                80
            );
        } elseif ($headPhotoValue !== '' && $headPhotoValue !== $currentPhotoValue) {
            $this->deleteStoredMediaIfLocal($currentPhotoValue);
        } elseif ($headPhotoValue === '' && $currentPhotoValue !== '') {
            $this->deleteStoredMediaIfLocal($currentPhotoValue);
        }

        $this->upsertSettings([
            'village_head_name' => trim((string) $data['head_name']),
            'village_head_position' => trim((string) $data['head_position']),
            'village_head_photo_url' => $headPhotoValue,
        ]);

        return redirect()->route('dashboard.website-settings.edit')
            ->with('success', 'Identitas kepala desa berhasil diperbarui.');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $payload = $this->validateStaff($request);
        $payload['sort_order'] = ((int) VillageStaff::max('sort_order') + 1);
        $payload['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('photo_file')) {
            $payload['photo_path'] = $this->imageUploadService->storeOptimized(
                $request->file('photo_file'),
                'website/staff',
                1200,
                1200,
                80
            );
        } elseif (! empty($payload['photo_url'])) {
            $payload['photo_path'] = $payload['photo_url'];
        }

        unset($payload['photo_url']);
        VillageStaff::create($payload);

        return redirect()->route('dashboard.website-settings.edit')
            ->with('success', 'Data perangkat desa berhasil ditambahkan.');
    }

    public function updateStaff(Request $request, VillageStaff $villageStaff): RedirectResponse
    {
        $payload = $this->validateStaff($request);
        $payload['is_active'] = $request->boolean('is_active');
        $incomingPhotoUrl = trim((string) ($payload['photo_url'] ?? ''));

        if ($request->hasFile('photo_file')) {
            $this->deleteStoredMediaIfLocal((string) $villageStaff->photo_path);
            $payload['photo_path'] = $this->imageUploadService->storeOptimized(
                $request->file('photo_file'),
                'website/staff',
                1200,
                1200,
                80
            );
        } elseif ($incomingPhotoUrl !== '') {
            if ($incomingPhotoUrl !== (string) $villageStaff->photo_path) {
                $this->deleteStoredMediaIfLocal((string) $villageStaff->photo_path);
            }
            $payload['photo_path'] = $incomingPhotoUrl;
        }

        unset($payload['photo_url']);
        $villageStaff->update($payload);

        return redirect()->route('dashboard.website-settings.edit')
            ->with('success', 'Data perangkat desa berhasil diperbarui.');
    }

    public function destroyStaff(VillageStaff $villageStaff): RedirectResponse
    {
        $this->deleteStoredMediaIfLocal((string) $villageStaff->photo_path);
        $villageStaff->delete();

        return redirect()->route('dashboard.website-settings.edit')
            ->with('success', 'Data perangkat desa berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStaff(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:160'],
            'is_active' => ['nullable', Rule::in(['1'])],
            'photo_url' => ['nullable', 'url', 'max:2048'],
            'photo_file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=5000,max_height=5000',
                'max:4096',
            ],
        ]);
    }

    /**
     * @param array<string, string> $settings
     */
    private function upsertSettings(array $settings): void
    {
        $userId = auth()->id();
        $timestamp = now();
        $rows = [];

        foreach ($settings as $key => $value) {
            $rows[] = [
                'key' => $key,
                'value' => trim((string) $value) !== '' ? (string) $value : null,
                'updated_by' => $userId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        WebsiteSetting::query()->upsert(
            $rows,
            ['key'],
            ['value', 'updated_by', 'updated_at']
        );

        Cache::forget(self::VILLAGE_CONFIG_CACHE_KEY);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function settingOrConfig(array $settings, string $settingKey, string $configKey): string
    {
        $value = isset($settings[$settingKey]) ? trim((string) $settings[$settingKey]) : '';
        if ($value !== '') {
            return $value;
        }

        return (string) config($configKey, '');
    }

    /**
     * @param array<int, mixed> $fallback
     * @return array<int, string>
     */
    private function decodeGallery(?string $rawValue, array $fallback): array
    {
        $value = trim((string) $rawValue);
        if ($value === '') {
            return array_values(array_filter(
                array_map(static fn ($item) => trim((string) $item), $fallback),
                static fn (string $item) => $item !== ''
            ));
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($item) => trim((string) $item), $decoded),
            static fn (string $item) => $item !== ''
        ));
    }

    private function normalizeGalleryAsJson(string $raw): ?string
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $items = array_values(array_filter(
            array_map(static fn (string $line) => trim($line), $lines),
            static fn (string $line) => $line !== ''
        ));

        return $items !== [] ? json_encode($items, JSON_UNESCAPED_SLASHES) : null;
    }

    private function deleteStoredMediaIfLocal(string $value): void
    {
        $this->imageUploadService->delete($value);
    }
}
