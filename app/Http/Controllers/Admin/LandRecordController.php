<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandRecord;
use App\Models\PopulationRecord;
use App\Services\ImageUploadService;
use App\Support\PublicMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LandRecordController extends Controller
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
        $hamlet = trim((string) $request->query('hamlet', ''));
        $status = trim((string) $request->query('status', ''));

        $baseQuery = LandRecord::query()
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($builder) use ($keyword): void {
                    $builder->where('land_code', 'like', '%' . $keyword . '%')
                        ->orWhere('location', 'like', '%' . $keyword . '%')
                        ->orWhere('owner_name', 'like', '%' . $keyword . '%')
                        ->orWhere('certificate_number', 'like', '%' . $keyword . '%')
                        ->orWhere('tax_object_number', 'like', '%' . $keyword . '%');
                });
            })
            ->when($hamlet !== '', fn ($query) => $query->where('hamlet', $hamlet))
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        $items = (clone $baseQuery)
            ->orderBy('hamlet')
            ->orderBy('location')
            ->paginate(25)
            ->withQueryString();

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_items, COALESCE(SUM(area_m2), 0) as total_area')
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

        $hamletOptions = LandRecord::query()
            ->whereNotNull('hamlet')
            ->where('hamlet', '!=', '')
            ->select('hamlet')
            ->distinct()
            ->orderBy('hamlet')
            ->pluck('hamlet');

        $statusOptions = LandRecord::query()
            ->whereNotNull('status')
            ->where('status', '!=', '')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('dashboard.land.index', [
            'items' => $items,
            'totalItems' => (int) ($summary?->total_items ?? 0),
            'totalArea' => (float) ($summary?->total_area ?? 0),
            'categorySummary' => $categorySummary,
            'statusSummary' => $statusSummary,
            'hamletOptions' => $hamletOptions,
            'statusOptions' => $statusOptions,
            'filters' => [
                'q' => $keyword,
                'hamlet' => $hamlet,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.land.form', [
            'item' => new LandRecord(['status' => 'Aktif']),
            'method' => 'POST',
            'route' => route('dashboard.land-records.store'),
            'title' => 'Tambah Data Pertanahan',
            'hamlets' => PopulationRecord::HAMLETS,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $this->storeFiles($request, $data);
        LandRecord::create($data);

        return redirect()->route('dashboard.land-records.index')->with('success', 'Data pertanahan berhasil ditambahkan.');
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
    public function edit(LandRecord $landRecord)
    {
        return view('dashboard.land.form', [
            'item' => $landRecord,
            'method' => 'PUT',
            'route' => route('dashboard.land-records.update', $landRecord),
            'title' => 'Edit Data Pertanahan',
            'hamlets' => PopulationRecord::HAMLETS,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LandRecord $landRecord)
    {
        $data = $this->validatePayload($request);
        $this->storeFiles($request, $data, $landRecord);
        $landRecord->update($data);

        return redirect()->route('dashboard.land-records.index')->with('success', 'Data pertanahan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LandRecord $landRecord)
    {
        $photoPath = PublicMedia::normalizePath((string) $landRecord->photo_path);
        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        $documentPath = PublicMedia::normalizePath((string) $landRecord->document_path);
        if ($documentPath) {
            Storage::disk('public')->delete($documentPath);
        }

        $landRecord->delete();

        return redirect()->route('dashboard.land-records.index')->with('success', 'Data pertanahan berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'land_code' => ['nullable', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'hamlet' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'area_m2' => ['required', 'numeric', 'min:0'],
            'ownership_status' => ['required', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'tax_object_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'photo' => [
                'nullable',
                'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=7000,max_height=7000',
                'max:3072',
            ],
            'document' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'max:5120',
            ],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function storeFiles(Request $request, array &$data, ?LandRecord $landRecord = null): void
    {
        unset($data['photo'], $data['document']);

        if ($request->hasFile('photo')) {
            $oldPhotoPath = $landRecord ? PublicMedia::normalizePath((string) $landRecord->photo_path) : null;
            if ($oldPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
            }

            $data['photo_path'] = $this->imageUploadService->storeOptimized(
                $request->file('photo'),
                'land/photos',
                1920,
                1920,
                78
            );
        }

        if ($request->hasFile('document')) {
            $oldDocumentPath = $landRecord ? PublicMedia::normalizePath((string) $landRecord->document_path) : null;
            if ($oldDocumentPath) {
                Storage::disk('public')->delete($oldDocumentPath);
            }

            $documentFile = $request->file('document');
            if (str_starts_with((string) $documentFile->getMimeType(), 'image/')) {
                $data['document_path'] = $this->imageUploadService->storeOptimized(
                    $documentFile,
                    'land/documents',
                    1920,
                    1920,
                    78
                );
            } else {
                $data['document_path'] = $documentFile->store('land/documents', 'public');
            }
        }
    }
}
