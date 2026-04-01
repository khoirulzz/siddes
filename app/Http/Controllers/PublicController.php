<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Gallery;
use App\Models\LandRecord;
use App\Models\News;
use App\Models\PopulationRecord;
use App\Models\VillageStaff;
use App\Models\VillageActivity;
use App\Support\PublicMedia;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    private const AGE_BRACKETS = [
        ['label' => 'Balita (0-5 tahun)', 'min' => 0, 'max' => 5],
        ['label' => 'Kanak-kanak (6-11 tahun)', 'min' => 6, 'max' => 11],
        ['label' => 'Remaja Awal (12-16 tahun)', 'min' => 12, 'max' => 16],
        ['label' => 'Remaja Akhir (17-25 tahun)', 'min' => 17, 'max' => 25],
        ['label' => 'Dewasa Awal (26-35 tahun)', 'min' => 26, 'max' => 35],
        ['label' => 'Dewasa Akhir (36-45 tahun)', 'min' => 36, 'max' => 45],
        ['label' => 'Lansia Awal (46-55 tahun)', 'min' => 46, 'max' => 55],
        ['label' => 'Lansia Akhir (56-65 tahun)', 'min' => 56, 'max' => 65],
        ['label' => 'Manula (>65 tahun)', 'min' => 66, 'max' => null],
    ];

    private const EDUCATION_BUCKETS = [
        'SD/Sederajat',
        'SMP/Sederajat',
        'SMA/Sederajat',
        'Diploma I/II',
        'Diploma III',
        'Diploma IV/Sarjana',
        'Magister',
        'Doktoral',
        'Lainnya / Belum Diisi',
    ];

    public function home()
    {
        $populationSummary = PopulationRecord::query()
            ->selectRaw('hamlet, COUNT(*) as total')
            ->groupBy('hamlet')
            ->orderBy('hamlet')
            ->get();

        $activityCategorySummary = VillageActivity::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->pluck('total', 'category');

        $activityBudgetSummary = VillageActivity::query()
            ->selectRaw('YEAR(activity_date) as year, COALESCE(SUM(budget), 0) as total_budget')
            ->whereNotNull('activity_date')
            ->groupByRaw('YEAR(activity_date)')
            ->orderByRaw('YEAR(activity_date)')
            ->pluck('total_budget', 'year');

        return view('public.home', [
            'news' => News::published()->latest('published_at')->take(4)->get(),
            'announcements' => Announcement::active()->latest()->take(2)->get(),
            'galleries' => Gallery::latest()->take(5)->get(),
            'populationChart' => [
                'labels' => $populationSummary->pluck('hamlet'),
                'data' => $populationSummary->pluck('total'),
            ],
            'activitiesChart' => [
                'labels' => $activityCategorySummary->keys()->values(),
                'data' => $activityCategorySummary->values(),
            ],
            'budgetChart' => [
                'labels' => $activityBudgetSummary->keys()->values(),
                'data' => $activityBudgetSummary->values(),
            ],
        ]);
    }

    public function profile()
    {
        $headPhotoValue = trim((string) config('village.head_photo_url', ''));
        $headPhoto = PublicMedia::toUrl($headPhotoValue) ?: ($headPhotoValue !== '' ? $headPhotoValue : null);
        $villageHead = [
            'name' => trim((string) config('village.head_name', 'ABDUL HADI')),
            'position' => trim((string) config('village.head_position', 'Kepala Desa Lambanggelun')),
            'photo' => $headPhoto ?: 'https://i.pravatar.cc/300?img=11',
        ];

        $staffMembers = VillageStaff::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static fn (VillageStaff $member): array => [
                'name' => $member->name,
                'position' => $member->position,
                'photo' => $member->photo_url ?: 'https://i.pravatar.cc/300?img=11',
            ])
            ->values()
            ->all();

        if ($staffMembers === []) {
            $staffMembers = [
                ['name' => 'Ulum Prasetyo', 'position' => 'Kepala Desa', 'photo' => 'https://i.pravatar.cc/300?img=11'],
                ['name' => 'Nanik Wulandari', 'position' => 'Sekretaris Desa', 'photo' => 'https://i.pravatar.cc/300?img=20'],
                ['name' => 'Rizal Maulana', 'position' => 'Kaur Tata Usaha dan Umum', 'photo' => 'https://i.pravatar.cc/300?img=15'],
                ['name' => 'Dewi Kartika', 'position' => 'Kaur Keuangan', 'photo' => 'https://i.pravatar.cc/300?img=25'],
                ['name' => 'Arif Setiawan', 'position' => 'Kaur Perencanaan', 'photo' => 'https://i.pravatar.cc/300?img=14'],
                ['name' => 'Siti Maesaroh', 'position' => 'Kasi Pemerintahan', 'photo' => 'https://i.pravatar.cc/300?img=32'],
                ['name' => 'Bagus Rahmad', 'position' => 'Kasi Kesejahteraan', 'photo' => 'https://i.pravatar.cc/300?img=18'],
                ['name' => 'Lina Marlina', 'position' => 'Kasi Pelayanan', 'photo' => 'https://i.pravatar.cc/300?img=27'],
                ['name' => 'Ahmad Fauzi', 'position' => 'Kepala Dusun Bojongireng', 'photo' => 'https://i.pravatar.cc/300?img=12'],
                ['name' => 'Rohman Hakim', 'position' => 'Kepala Dusun Panumbangan', 'photo' => 'https://i.pravatar.cc/300?img=13'],
                ['name' => 'Mochammad Ridwan', 'position' => 'Kepala Dusun Mandelun', 'photo' => 'https://i.pravatar.cc/300?img=16'],
                ['name' => 'Budi Santoso', 'position' => 'Kepala Dusun Sasak', 'photo' => 'https://i.pravatar.cc/300?img=17'],
                ['name' => 'Slamet Riyadi', 'position' => 'Kepala Dusun Simendem', 'photo' => 'https://i.pravatar.cc/300?img=19'],
            ];
        }

        return view('public.profile', [
            'villagePhotos' => config('village.profile_gallery_images', []),
            'villageHead' => $villageHead,
            'staffMembers' => $staffMembers,
        ]);
    }

    public function population()
    {
        $summaryByHamlet = PopulationRecord::query()
            ->selectRaw("COALESCE(dusun, hamlet) as hamlet_name, COUNT(*) as total, SUM(CASE WHEN COALESCE(jenis_kelamin, gender) = 'Laki-laki' THEN 1 ELSE 0 END) as male_total, SUM(CASE WHEN COALESCE(jenis_kelamin, gender) = 'Perempuan' THEN 1 ELSE 0 END) as female_total")
            ->groupByRaw('COALESCE(dusun, hamlet)')
            ->orderByRaw('COALESCE(dusun, hamlet)')
            ->get();

        $genderSummary = PopulationRecord::query()
            ->selectRaw('COALESCE(jenis_kelamin, gender) as gender_name, COUNT(*) as total')
            ->groupByRaw('COALESCE(jenis_kelamin, gender)')
            ->pluck('total', 'gender_name');

        $chartResidents = PopulationRecord::query()
            ->get(['id', 'tanggal_lahir', 'birth_date', 'pendidikan']);

        return view('public.information.population', [
            'totalResidents' => (int) $summaryByHamlet->sum('total'),
            'summaryByHamlet' => $summaryByHamlet,
            'genderSummary' => [
                'Laki-laki' => (int) ($genderSummary['Laki-laki'] ?? 0),
                'Perempuan' => (int) ($genderSummary['Perempuan'] ?? 0),
            ],
            'ageSummary' => $this->buildAgeSummary($chartResidents),
            'educationSummary' => $this->buildEducationSummary($chartResidents),
        ]);
    }

    public function land()
    {
        $summary = LandRecord::query()
            ->selectRaw('COUNT(*) as total_records, COALESCE(SUM(area_m2), 0) as total_area')
            ->first();

        $categorySummary = LandRecord::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $statusSummary = LandRecord::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return view('public.information.land', [
            'totalRecords' => (int) ($summary?->total_records ?? 0),
            'totalArea' => (float) ($summary?->total_area ?? 0),
            'categorySummary' => $categorySummary,
            'statusSummary' => $statusSummary,
        ]);
    }

    public function activities()
    {
        $activities = VillageActivity::query()
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $summary = VillageActivity::query()
            ->selectRaw(
                'COUNT(*) as total,
                 COALESCE(SUM(budget), 0) as total_budget,
                 SUM(CASE WHEN budget IS NOT NULL THEN 1 ELSE 0 END) as with_budget,
                 SUM(CASE WHEN budget IS NULL THEN 1 ELSE 0 END) as without_budget'
            )
            ->first();

        $categorySummary = VillageActivity::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $statusSummary = VillageActivity::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $budgetYearSummary = VillageActivity::query()
            ->selectRaw('YEAR(activity_date) as year, COALESCE(SUM(budget), 0) as total_budget')
            ->whereNotNull('activity_date')
            ->groupByRaw('YEAR(activity_date)')
            ->orderByRaw('YEAR(activity_date)')
            ->get();

        return view('public.information.activities', [
            'activities' => $activities,
            'summary' => [
                'total' => (int) ($summary?->total ?? 0),
                'total_budget' => (float) ($summary?->total_budget ?? 0),
                'with_budget' => (int) ($summary?->with_budget ?? 0),
                'without_budget' => (int) ($summary?->without_budget ?? 0),
            ],
            'categoryChart' => [
                'labels' => $categorySummary->pluck('category')->values(),
                'data' => $categorySummary->pluck('total')->values(),
            ],
            'statusChart' => [
                'labels' => $statusSummary->pluck('status')->values(),
                'data' => $statusSummary->pluck('total')->values(),
            ],
            'budgetYearChart' => [
                'labels' => $budgetYearSummary->pluck('year')->values(),
                'data' => $budgetYearSummary->pluck('total_budget')->values(),
            ],
        ]);
    }

    public function newsIndex()
    {
        return view('public.news.index', [
            'news' => News::published()
                ->latest('published_at')
                ->paginate(9)
                ->withQueryString(),
        ]);
    }

    public function newsShow(News $news)
    {
        if (! $news->is_published) {
            abort(404);
        }

        return view('public.news.show', [
            'news' => $news,
        ]);
    }

    public function galleryIndex()
    {
        return view('public.gallery.index', [
            'galleries' => Gallery::latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function announcementIndex()
    {
        return view('public.announcements.index', [
            'announcements' => Announcement::active()
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function announcementShow(Announcement $announcement)
    {
        $isActive = Announcement::query()
            ->active()
            ->whereKey($announcement->id)
            ->exists();

        if (! $isActive) {
            abort(404);
        }

        return view('public.announcements.show', [
            'announcement' => $announcement,
        ]);
    }

    /**
     * @param Collection<int, PopulationRecord> $residents
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildAgeSummary(Collection $residents): array
    {
        $counts = [];
        foreach (self::AGE_BRACKETS as $bracket) {
            $counts[$bracket['label']] = 0;
        }

        foreach ($residents as $resident) {
            $age = $resident->age;
            if ($age === null || $age < 0) {
                continue;
            }

            foreach (self::AGE_BRACKETS as $bracket) {
                if ($age < $bracket['min']) {
                    continue;
                }

                if ($bracket['max'] !== null && $age > $bracket['max']) {
                    continue;
                }

                $counts[$bracket['label']]++;
                break;
            }
        }

        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts),
        ];
    }

    /**
     * @param Collection<int, PopulationRecord> $residents
     * @return array{labels:array<int,string>,data:array<int,int>}
     */
    private function buildEducationSummary(Collection $residents): array
    {
        $counts = array_fill_keys(self::EDUCATION_BUCKETS, 0);

        foreach ($residents as $resident) {
            $bucket = $this->normalizeEducationBucket($resident->pendidikan);
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        }

        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts),
        ];
    }

    private function normalizeEducationBucket(?string $value): string
    {
        $normalized = Str::lower(trim((string) $value));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?: '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?: '');

        if ($normalized === '') {
            return 'Lainnya / Belum Diisi';
        }

        if (Str::contains($normalized, ['s3', 'strata 3', 'doktor', 'doktoral', 'phd'])) {
            return 'Doktoral';
        }

        if (Str::contains($normalized, ['s2', 'strata 2', 'magister', 'master'])) {
            return 'Magister';
        }

        if (Str::contains($normalized, ['d4', 'd 4', 'd iv', 'diploma 4', 'diploma iv', 's1', 'strata 1', 'sarjana'])) {
            return 'Diploma IV/Sarjana';
        }

        if (Str::contains($normalized, ['d3', 'd 3', 'd iii', 'diploma 3', 'diploma iii'])) {
            return 'Diploma III';
        }

        if (Str::contains($normalized, ['d1', 'd 1', 'd i', 'diploma 1', 'diploma i', 'd2', 'd 2', 'd ii', 'diploma 2', 'diploma ii'])) {
            return 'Diploma I/II';
        }

        if (Str::contains($normalized, ['sma', 'smk', 'slta', 'madrasah aliyah', 'aliyah', 'paket c']) || preg_match('/\bma\b/', $normalized) === 1) {
            return 'SMA/Sederajat';
        }

        if (Str::contains($normalized, ['smp', 'sltp', 'madrasah tsanawiyah', 'tsanawiyah', 'paket b']) || preg_match('/\bmts\b/', $normalized) === 1) {
            return 'SMP/Sederajat';
        }

        if (Str::contains($normalized, ['sd', 'sekolah dasar', 'madrasah ibtidaiyah', 'ibtidaiyah', 'paket a']) || preg_match('/\bmi\b/', $normalized) === 1) {
            return 'SD/Sederajat';
        }

        return 'Lainnya / Belum Diisi';
    }
}
