<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ComplaintReport;
use App\Models\Gallery;
use App\Models\LandRecord;
use App\Models\LetterServiceRequest;
use App\Models\News;
use App\Models\PbbPaymentRequest;
use App\Models\PopulationRecord;
use App\Models\VillageActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        [$selectedPeriod, $periodLabel, $periodStart, $periodEnd] = $this->resolveMonitoringPeriod(
            (string) $request->query('period', 'today')
        );

        [$monitorCards, $stats] = $this->buildMonitoringCards($periodStart, $periodEnd);

        $populationSummary = PopulationRecord::query()
            ->selectRaw('hamlet, COUNT(*) as total')
            ->groupBy('hamlet')
            ->orderBy('hamlet')
            ->get();

        $activitiesSummary = VillageActivity::query()
            ->orderBy('activity_date')
            ->get();

        return view('dashboard.index', [
            'monitorCards' => $monitorCards,
            'periodOptions' => [
                'today' => 'Hari Ini',
                'yesterday' => 'Kemarin',
                'week' => '7 Hari Terakhir',
                'month' => 'Bulan Ini',
                'year' => 'Tahun Ini',
            ],
            'selectedPeriod' => $selectedPeriod,
            'periodLabel' => $periodLabel,
            'stats' => $stats,
            'populationChart' => [
                'labels' => $populationSummary->pluck('hamlet'),
                'data' => $populationSummary->pluck('total'),
            ],
            'activitiesChart' => [
                'labels' => $activitiesSummary
                    ->groupBy('category')
                    ->keys()
                    ->values(),
                'data' => $activitiesSummary
                    ->groupBy('category')
                    ->map->count()
                    ->values(),
            ],
            'budgetChart' => [
                'labels' => $activitiesSummary
                    ->groupBy(fn (VillageActivity $activity) => (string) $activity->activity_date?->format('Y'))
                    ->sortKeys()
                    ->keys()
                    ->values(),
                'data' => $activitiesSummary
                    ->groupBy(fn (VillageActivity $activity) => (string) $activity->activity_date?->format('Y'))
                    ->sortKeys()
                    ->map(fn ($group) => (float) $group->sum('budget'))
                    ->values(),
            ],
        ]);
    }

    public function monitoringSummary(Request $request): JsonResponse
    {
        [$selectedPeriod, $periodLabel, $periodStart, $periodEnd] = $this->resolveMonitoringPeriod(
            (string) $request->query('period', 'today')
        );

        [$monitorCards] = $this->buildMonitoringCards($periodStart, $periodEnd);

        return response()->json([
            'period' => [
                'key' => $selectedPeriod,
                'label' => $periodLabel,
            ],
            'cards' => $monitorCards,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array{0:string,1:string,2:\Illuminate\Support\Carbon,3:\Illuminate\Support\Carbon}
     */
    private function resolveMonitoringPeriod(string $period): array
    {
        $period = in_array($period, ['today', 'yesterday', 'week', 'month', 'year'], true) ? $period : 'today';
        $now = now();

        return match ($period) {
            'yesterday' => [
                'yesterday',
                'Kemarin',
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'week' => [
                'week',
                '7 Hari Terakhir',
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'month' => [
                'month',
                'Bulan Ini',
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            'year' => [
                'year',
                'Tahun Ini',
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ],
            default => [
                'today',
                'Hari Ini',
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * @return array{0:array<int,array<string,mixed>>,1:array<string,int>}
     */
    private function buildMonitoringCards($periodStart, $periodEnd): array
    {
        $letterStats = LetterServiceRequest::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN COALESCE(submitted_at, created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as period_count,
                 SUM(CASE WHEN status = ? AND COALESCE(submitted_at, created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as incoming_count',
                [$periodStart, $periodEnd, 'Diajukan', $periodStart, $periodEnd]
            )
            ->first();

        $letterPeriodCount = (int) ($letterStats?->period_count ?? 0);
        $letterTotalAll = (int) ($letterStats?->total_all ?? 0);
        $letterIncoming = (int) ($letterStats?->incoming_count ?? 0);

        $pbbStats = PbbPaymentRequest::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN COALESCE(submitted_at, created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as period_count,
                 SUM(CASE WHEN status = ? AND COALESCE(submitted_at, created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as incoming_count',
                [$periodStart, $periodEnd, 'Diajukan', $periodStart, $periodEnd]
            )
            ->first();

        $pbbPeriodCount = (int) ($pbbStats?->period_count ?? 0);
        $pbbTotalAll = (int) ($pbbStats?->total_all ?? 0);
        $pbbIncoming = (int) ($pbbStats?->incoming_count ?? 0);

        $complaintStats = ComplaintReport::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN COALESCE(submitted_at, created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as period_count,
                 SUM(CASE WHEN status = ? AND COALESCE(submitted_at, created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as incoming_count',
                [$periodStart, $periodEnd, 'Diterima', $periodStart, $periodEnd]
            )
            ->first();

        $complaintPeriodCount = (int) ($complaintStats?->period_count ?? 0);
        $complaintTotalAll = (int) ($complaintStats?->total_all ?? 0);
        $complaintIncoming = (int) ($complaintStats?->incoming_count ?? 0);

        $totalServiceRequests = $pbbPeriodCount + $letterPeriodCount + $complaintPeriodCount;
        $totalServiceIncoming = $pbbIncoming + $letterIncoming + $complaintIncoming;
        $totalServiceAll = $pbbTotalAll + $letterTotalAll + $complaintTotalAll;

        $populationStats = PopulationRecord::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_count',
                [$periodStart, $periodEnd]
            )
            ->first();
        $populationTotalAll = (int) ($populationStats?->total_all ?? 0);
        $populationNew = (int) ($populationStats?->new_count ?? 0);

        $landStats = LandRecord::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_count',
                [$periodStart, $periodEnd]
            )
            ->first();
        $landTotalAll = (int) ($landStats?->total_all ?? 0);
        $landNew = (int) ($landStats?->new_count ?? 0);

        $activityStats = VillageActivity::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_count',
                [$periodStart, $periodEnd]
            )
            ->first();
        $activityTotalAll = (int) ($activityStats?->total_all ?? 0);
        $activityNew = (int) ($activityStats?->new_count ?? 0);

        $announcementStats = Announcement::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_count',
                [$periodStart, $periodEnd]
            )
            ->first();
        $announcementTotalAll = (int) ($announcementStats?->total_all ?? 0);
        $announcementNew = (int) ($announcementStats?->new_count ?? 0);

        $newsStats = News::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_count',
                [$periodStart, $periodEnd]
            )
            ->first();
        $newsTotalAll = (int) ($newsStats?->total_all ?? 0);
        $newsNew = (int) ($newsStats?->new_count ?? 0);

        $galleryStats = Gallery::query()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_count',
                [$periodStart, $periodEnd]
            )
            ->first();
        $galleryTotalAll = (int) ($galleryStats?->total_all ?? 0);
        $galleryNew = (int) ($galleryStats?->new_count ?? 0);

        return [[
            [
                'key' => 'layanan_masuk',
                'title' => 'Layanan Masuk',
                'value' => $totalServiceRequests,
                'total' => $totalServiceAll,
                'route' => null,
                'tone' => 'ocean',
                'notification' => $totalServiceIncoming,
            ],
            [
                'key' => 'surat_masuk',
                'title' => 'Surat Masuk',
                'value' => $letterPeriodCount,
                'total' => $letterTotalAll,
                'route' => route('dashboard.letter-service-requests.index'),
                'tone' => 'teal',
                'notification' => $letterIncoming,
            ],
            [
                'key' => 'pbb_masuk',
                'title' => 'PBB Masuk',
                'value' => $pbbPeriodCount,
                'total' => $pbbTotalAll,
                'route' => route('dashboard.pbb-payment-requests.index'),
                'tone' => 'blue',
                'notification' => $pbbIncoming,
            ],
            [
                'key' => 'pengaduan_masuk',
                'title' => 'Pengaduan Masuk',
                'value' => $complaintPeriodCount,
                'total' => $complaintTotalAll,
                'route' => route('dashboard.complaint-reports.index'),
                'tone' => 'rose',
                'notification' => $complaintIncoming,
            ],
            [
                'key' => 'penduduk_baru',
                'title' => 'Penduduk Baru',
                'value' => $populationNew,
                'total' => $populationTotalAll,
                'route' => route('dashboard.population-records.index'),
                'tone' => 'mint',
                'notification' => $populationNew,
            ],
            [
                'key' => 'pertanahan_baru',
                'title' => 'Pertanahan Baru',
                'value' => $landNew,
                'total' => $landTotalAll,
                'route' => route('dashboard.land-records.index'),
                'tone' => 'amber',
                'notification' => $landNew,
            ],
            [
                'key' => 'kegiatan_baru',
                'title' => 'Kegiatan Baru',
                'value' => $activityNew,
                'total' => $activityTotalAll,
                'route' => route('dashboard.village-activities.index'),
                'tone' => 'violet',
                'notification' => $activityNew,
            ],
            [
                'key' => 'pengumuman_baru',
                'title' => 'Pengumuman Baru',
                'value' => $announcementNew,
                'total' => $announcementTotalAll,
                'route' => route('dashboard.announcements.index'),
                'tone' => 'sky',
                'notification' => $announcementNew,
            ],
            [
                'key' => 'berita_baru',
                'title' => 'Berita Baru',
                'value' => $newsNew,
                'total' => $newsTotalAll,
                'route' => route('dashboard.news.index'),
                'tone' => 'slate',
                'notification' => $newsNew,
            ],
            [
                'key' => 'galeri_baru',
                'title' => 'Galeri Baru',
                'value' => $galleryNew,
                'total' => $galleryTotalAll,
                'route' => route('dashboard.galleries.index'),
                'tone' => 'indigo',
                'notification' => $galleryNew,
            ],
        ], [
            'layanan_masuk' => $totalServiceRequests,
            'pengaduan' => $complaintIncoming,
            'surat_total' => $letterTotalAll,
            'surat_masuk' => $letterIncoming,
        ]];
    }

    public function placeholder(string $module)
    {
        $placeholders = [
            'arsip' => [
                'title' => 'Arsip Layanan',
                'description' => 'Modul arsip layanan sudah aktif. Silakan gunakan menu Arsip Layanan untuk melihat arsip surat, PBB, dan pengaduan.',
            ],
            'pengaturan-website' => [
                'title' => 'Pengaturan Website',
                'description' => 'Pengelolaan profil desa, banner homepage, dan konfigurasi website lanjutan disiapkan pada fase pengembangan berikutnya.',
            ],
        ];

        abort_unless(array_key_exists($module, $placeholders), 404);

        return view('dashboard.placeholder', $placeholders[$module]);
    }
}
