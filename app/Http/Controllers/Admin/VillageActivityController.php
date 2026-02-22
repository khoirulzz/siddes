<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VillageActivity;
use App\Services\ImageUploadService;
use App\Support\PublicMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VillageActivityController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploadService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $status = trim((string) $request->query('status', ''));
        $year = trim((string) $request->query('year', ''));

        $baseQuery = VillageActivity::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($builder) use ($keyword): void {
                    $builder->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('location', 'like', '%' . $keyword . '%')
                        ->orWhere('person_in_charge', 'like', '%' . $keyword . '%')
                        ->orWhere('summary', 'like', '%' . $keyword . '%');
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when(
                $year !== '' && ctype_digit($year),
                fn ($query) => $query->whereYear('activity_date', (int) $year)
            );

        $items = (clone $baseQuery)
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $summary = (clone $baseQuery)
            ->selectRaw(
                'COUNT(*) as total_items,
                 COALESCE(SUM(budget), 0) as total_budget,
                 SUM(CASE WHEN budget IS NOT NULL THEN 1 ELSE 0 END) as with_budget,
                 SUM(CASE WHEN budget IS NULL THEN 1 ELSE 0 END) as without_budget'
            )
            ->first();

        $categorySummary = (clone $baseQuery)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $statusSummary = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $yearlyBudgetSummary = (clone $baseQuery)
            ->selectRaw('YEAR(activity_date) as year, COALESCE(SUM(budget), 0) as total_budget')
            ->groupByRaw('YEAR(activity_date)')
            ->orderByRaw('YEAR(activity_date)')
            ->get();

        $categoryOptions = VillageActivity::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $statusOptions = VillageActivity::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $yearOptions = VillageActivity::query()
            ->selectRaw('YEAR(activity_date) as year')
            ->whereNotNull('activity_date')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($item) => (string) $item);

        return view('dashboard.activities.index', [
            'items' => $items,
            'categorySummary' => $categorySummary,
            'statusSummary' => $statusSummary,
            'yearlyBudgetSummary' => $yearlyBudgetSummary,
            'totalItems' => (int) ($summary?->total_items ?? 0),
            'totalBudget' => (float) ($summary?->total_budget ?? 0),
            'withBudget' => (int) ($summary?->with_budget ?? 0),
            'withoutBudget' => (int) ($summary?->without_budget ?? 0),
            'categoryOptions' => $categoryOptions,
            'statusFilterOptions' => $statusOptions,
            'yearOptions' => $yearOptions,
            'filters' => [
                'q' => $keyword,
                'category' => $category,
                'status' => $status,
                'year' => $year,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.activities.form', [
            'item' => new VillageActivity([
                'activity_date' => now()->toDateString(),
                'status' => 'Perencanaan',
            ]),
            'method' => 'POST',
            'route' => route('dashboard.village-activities.store'),
            'title' => 'Tambah Kegiatan Desa',
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->generateSlug($data['title']);
        $this->storeFiles($request, $data);

        VillageActivity::create($data);

        return redirect()->route('dashboard.village-activities.index')->with('success', 'Data kegiatan desa berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VillageActivity $villageActivity)
    {
        return view('dashboard.activities.form', [
            'item' => $villageActivity,
            'method' => 'PUT',
            'route' => route('dashboard.village-activities.update', $villageActivity),
            'title' => 'Edit Kegiatan Desa',
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VillageActivity $villageActivity)
    {
        $data = $this->validatePayload($request);
        if ($villageActivity->title !== $data['title']) {
            $data['slug'] = $this->generateSlug($data['title'], $villageActivity->id);
        }

        $this->storeFiles($request, $data, $villageActivity);
        $villageActivity->update($data);

        return redirect()->route('dashboard.village-activities.index')->with('success', 'Data kegiatan desa berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VillageActivity $villageActivity)
    {
        $coverPath = PublicMedia::normalizePath((string) $villageActivity->cover_image_path);
        if ($coverPath) {
            Storage::disk('public')->delete($coverPath);
        }

        $documentPath = PublicMedia::normalizePath((string) $villageActivity->document_path);
        if ($documentPath) {
            Storage::disk('public')->delete($documentPath);
        }

        $villageActivity->delete();

        return redirect()->route('dashboard.village-activities.index')->with('success', 'Data kegiatan desa berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'person_in_charge' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in($this->statusOptions())],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'summary' => ['nullable', 'string', 'max:400'],
            'description' => ['nullable', 'string'],
            'cover_image' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=7000,max_height=7000',
                'max:4096',
            ],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,xlsx,xls,jpg,jpeg,png',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png',
                'max:8192',
            ],
        ]);
    }

    private function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $base = $slug;
        $counter = 1;

        while (VillageActivity::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function storeFiles(Request $request, array &$data, ?VillageActivity $item = null): void
    {
        unset($data['cover_image'], $data['document']);

        if ($request->hasFile('cover_image')) {
            $oldCoverPath = $item ? PublicMedia::normalizePath((string) $item->cover_image_path) : null;
            if ($oldCoverPath) {
                Storage::disk('public')->delete($oldCoverPath);
            }

            $data['cover_image_path'] = $this->imageUploadService->storeOptimized(
                $request->file('cover_image'),
                'activities/covers',
                1920,
                1920,
                78
            );
        }

        if ($request->hasFile('document')) {
            $oldDocumentPath = $item ? PublicMedia::normalizePath((string) $item->document_path) : null;
            if ($oldDocumentPath) {
                Storage::disk('public')->delete($oldDocumentPath);
            }

            $documentFile = $request->file('document');
            if (str_starts_with((string) $documentFile->getMimeType(), 'image/')) {
                $data['document_path'] = $this->imageUploadService->storeOptimized(
                    $documentFile,
                    'activities/documents',
                    1920,
                    1920,
                    78
                );
            } else {
                $data['document_path'] = $documentFile->store('activities/documents', 'public');
            }
        }
    }

    private function statusOptions(): array
    {
        return ['Perencanaan', 'Berjalan', 'Selesai', 'Ditunda'];
    }
}
