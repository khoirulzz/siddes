<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandTransaction;
use App\Models\LandTransactionFile;
use App\Services\CloudinaryService;
use App\Services\ImageUploadService;
use App\Support\MediaSecurity;
use App\Support\PublicMedia;
use App\Support\RemoteMediaResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LandTransactionController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService,
        private readonly CloudinaryService $cloudinaryService
    ) {
    }

    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'type' => trim((string) $request->query('type', '')),
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
        ];

        $baseQuery = $this->filteredTransactionsQuery($filters);

        $items = (clone $baseQuery)
            ->withCount('files')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_rows, COALESCE(SUM(area_m2), 0) as total_area')
            ->first();

        return view('dashboard.land-transactions.index', [
            'items' => $items,
            'filters' => $filters,
            'typeOptions' => LandTransaction::typeOptions(),
            'stats' => [
                'total_rows' => (int) ($summary?->total_rows ?? 0),
                'total_area' => (float) ($summary?->total_area ?? 0),
                'total_files' => LandTransactionFile::query()->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('dashboard.land-transactions.form', [
            'title' => 'Catat Transaksi Pertanahan',
            'item' => new LandTransaction([
                'transaction_date' => now()->toDateString(),
                'transaction_type' => 'jual_beli',
            ]),
            'typeOptions' => LandTransaction::typeOptions(),
            'route' => route('dashboard.land-transactions.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatePayload($request);

        $transaction = DB::transaction(function () use ($request, $payload) {
            $record = LandTransaction::create([
                ...$payload,
                'transaction_number' => $this->generateTransactionNumber(),
                'created_by' => $request->user()?->id,
            ]);

            $this->storeFiles($request, $record);

            return $record;
        });

        return redirect()
            ->route('dashboard.land-transactions.show', $transaction)
            ->with('success', 'Transaksi pertanahan berhasil dicatat.');
    }

    public function show(LandTransaction $landTransaction)
    {
        $landTransaction->load(['files.uploader', 'creator']);

        $pages = array_values(array_filter([
            trim((string) $landTransaction->party_a_page),
            trim((string) $landTransaction->party_b_page),
        ]));

        $related = LandTransaction::query()
            ->withCount('files')
            ->whereKeyNot($landTransaction->getKey())
            ->when($pages !== [], function (Builder $query) use ($pages): void {
                $query->where(function (Builder $builder) use ($pages): void {
                    $builder->whereIn('party_a_page', $pages)
                        ->orWhereIn('party_b_page', $pages);
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('dashboard.land-transactions.show', [
            'item' => $landTransaction,
            'related' => $related,
        ]);
    }

    public function edit(LandTransaction $landTransaction)
    {
        $landTransaction->load('files');

        return view('dashboard.land-transactions.form', [
            'title' => 'Edit Transaksi Pertanahan',
            'item' => $landTransaction,
            'typeOptions' => LandTransaction::typeOptions(),
            'route' => route('dashboard.land-transactions.update', $landTransaction),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, LandTransaction $landTransaction): RedirectResponse
    {
        $payload = $this->validatePayload($request);

        DB::transaction(function () use ($request, $landTransaction, $payload): void {
            $landTransaction->update($payload);

            /** @var array<int, int|string> $removeFileIds */
            $removeFileIds = (array) $request->input('remove_file_ids', []);
            if ($removeFileIds !== []) {
                $filesToDelete = $landTransaction->files()
                    ->whereIn('id', array_map('intval', $removeFileIds))
                    ->get();

                foreach ($filesToDelete as $file) {
                    $this->deleteStoredFile($file);
                }
            }

            $this->storeFiles($request, $landTransaction);
        });

        return redirect()
            ->route('dashboard.land-transactions.show', $landTransaction)
            ->with('success', 'Transaksi pertanahan berhasil diperbarui.');
    }

    public function destroy(LandTransaction $landTransaction): RedirectResponse
    {
        $landTransaction->load('files');

        DB::transaction(function () use ($landTransaction): void {
            foreach ($landTransaction->files as $file) {
                $this->deleteStoredFile($file);
            }

            $landTransaction->delete();
        });

        return redirect()
            ->route('dashboard.land-transactions.index')
            ->with('success', 'Transaksi pertanahan berhasil dihapus.');
    }

    public function history(Request $request)
    {
        $filters = [
            'name' => trim((string) $request->query('name', '')),
            'page' => trim((string) $request->query('page', '')),
        ];

        $items = $this->historyQuery($filters)
            ->withCount('files')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.land-transactions.history', [
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function archives(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'kind' => trim((string) $request->query('kind', '')),
        ];

        $items = LandTransactionFile::query()
            ->with('transaction')
            ->when($filters['kind'] === 'image', fn (Builder $query) => $query->where('mime_type', 'like', 'image/%'))
            ->when($filters['kind'] === 'pdf', fn (Builder $query) => $query->where('mime_type', 'application/pdf'))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $keyword = $filters['q'];

                $query->where(function (Builder $builder) use ($keyword): void {
                    $builder->where('original_name', 'like', '%' . $keyword . '%')
                        ->orWhere('file_path', 'like', '%' . $keyword . '%')
                        ->orWhereHas('transaction', function (Builder $transactionQuery) use ($keyword): void {
                            $transactionQuery->where('transaction_number', 'like', '%' . $keyword . '%')
                                ->orWhere('party_a_name', 'like', '%' . $keyword . '%')
                                ->orWhere('party_a_identifier', 'like', '%' . $keyword . '%')
                                ->orWhere('party_a_address', 'like', '%' . $keyword . '%')
                                ->orWhere('party_b_name', 'like', '%' . $keyword . '%')
                                ->orWhere('party_b_identifier', 'like', '%' . $keyword . '%')
                                ->orWhere('party_b_address', 'like', '%' . $keyword . '%')
                                ->orWhere('party_a_page', 'like', '%' . $keyword . '%')
                                ->orWhere('party_b_page', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.land-transactions.archives', [
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function showFile(Request $request, LandTransactionFile $landTransactionFile): Response|RedirectResponse
    {
        $path = trim((string) $landTransactionFile->file_path);
        abort_if($path === '', 404);

        $filename = $this->safeFilename(
            (string) ($landTransactionFile->original_name ?: basename((string) parse_url($path, PHP_URL_PATH)))
        );
        $mime = trim((string) $landTransactionFile->mime_type);
        $mime = $mime !== '' ? $mime : 'application/octet-stream';
        abort_unless(MediaSecurity::isAllowedMime($mime), 404);

        if (preg_match('/^https?:\/\//i', $path) === 1) {
            return $this->remoteFileResponse(
                $path,
                $filename,
                $mime,
                $request->query('mode') === 'download'
            );
        }

        $normalizedPath = PublicMedia::normalizePath($path);
        abort_if(! $normalizedPath, 404);
        abort_unless(MediaSecurity::isAllowedPath($normalizedPath), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($normalizedPath), 404);

        $mime = (string) ($landTransactionFile->mime_type ?: $disk->mimeType($normalizedPath) ?: 'application/octet-stream');
        abort_unless(MediaSecurity::isAllowedMime($mime), 404);

        $filename = $this->safeFilename((string) ($landTransactionFile->original_name ?: basename($normalizedPath)));

        if ($request->query('mode') === 'download') {
            return response()->download(
                $disk->path($normalizedPath),
                $filename,
                [
                    'Content-Type' => $mime,
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
        }

        return response()->file($disk->path($normalizedPath), [
            'Content-Type' => $mime,
            'Content-Disposition' => MediaSecurity::dispositionForMime($mime) . '; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyFile(LandTransactionFile $landTransactionFile): RedirectResponse
    {
        $this->deleteStoredFile($landTransactionFile);

        return back()->with('success', 'File arsip transaksi berhasil dihapus.');
    }

    /**
     * @param array{q:string,type:string,from:string,to:string} $filters
     */
    private function filteredTransactionsQuery(array $filters): Builder
    {
        return LandTransaction::query()
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $keyword = $filters['q'];
                $query->where(function (Builder $builder) use ($keyword): void {
                    $builder->where('transaction_number', 'like', '%' . $keyword . '%')
                        ->orWhere('party_a_name', 'like', '%' . $keyword . '%')
                        ->orWhere('party_a_identifier', 'like', '%' . $keyword . '%')
                        ->orWhere('party_a_address', 'like', '%' . $keyword . '%')
                        ->orWhere('party_b_name', 'like', '%' . $keyword . '%')
                        ->orWhere('party_b_identifier', 'like', '%' . $keyword . '%')
                        ->orWhere('party_b_address', 'like', '%' . $keyword . '%')
                        ->orWhere('party_a_page', 'like', '%' . $keyword . '%')
                        ->orWhere('party_b_page', 'like', '%' . $keyword . '%')
                        ->orWhere('document_number', 'like', '%' . $keyword . '%')
                        ->orWhere('land_object', 'like', '%' . $keyword . '%');
                });
            })
            ->when($filters['type'] !== '', fn (Builder $query) => $query->where('transaction_type', $filters['type']))
            ->when($filters['from'] !== '', fn (Builder $query) => $query->whereDate('transaction_date', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn (Builder $query) => $query->whereDate('transaction_date', '<=', $filters['to']));
    }

    /**
     * @param array{name:string,page:string} $filters
     */
    private function historyQuery(array $filters): Builder
    {
        return LandTransaction::query()
            ->when($filters['name'] !== '', function (Builder $query) use ($filters): void {
                $name = $filters['name'];
                $query->where(function (Builder $builder) use ($name): void {
                    $builder->where('party_a_name', 'like', '%' . $name . '%')
                        ->orWhere('party_a_identifier', 'like', '%' . $name . '%')
                        ->orWhere('party_b_name', 'like', '%' . $name . '%')
                        ->orWhere('party_b_identifier', 'like', '%' . $name . '%');
                });
            })
            ->when($filters['page'] !== '', function (Builder $query) use ($filters): void {
                $page = $filters['page'];
                $query->where(function (Builder $builder) use ($page): void {
                    $builder->where('party_a_page', $page)
                        ->orWhere('party_b_page', $page);
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', 'string', 'in:' . implode(',', array_keys(LandTransaction::typeOptions()))],
            'party_a_name' => ['required', 'string', 'max:160'],
            'party_a_identifier' => ['nullable', 'string', 'max:160'],
            'party_a_address' => ['required', 'string', 'max:500'],
            'party_a_page' => ['required', 'string', 'max:50'],
            'party_b_name' => ['required', 'string', 'max:160'],
            'party_b_identifier' => ['nullable', 'string', 'max:160'],
            'party_b_address' => ['required', 'string', 'max:500'],
            'party_b_page' => ['required', 'string', 'max:50'],
            'land_object' => ['required', 'string', 'max:5000'],
            'area_m2' => ['nullable', 'numeric', 'min:0'],
            'document_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp',
                'max:10240',
            ],
            'remove_file_ids' => ['nullable', 'array'],
            'remove_file_ids.*' => ['integer'],
        ]);
    }

    private function generateTransactionNumber(): string
    {
        do {
            $number = 'TNH-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
        } while (LandTransaction::query()->where('transaction_number', $number)->exists());

        return $number;
    }

    private function storeFiles(Request $request, LandTransaction $transaction): void
    {
        /** @var array<int, \Illuminate\Http\UploadedFile> $uploadedFiles */
        $uploadedFiles = array_filter((array) $request->file('files', []));
        if ($uploadedFiles === []) {
            return;
        }

        foreach ($uploadedFiles as $uploadedFile) {
            $mime = (string) $uploadedFile->getMimeType();
            $storedPath = str_starts_with($mime, 'image/')
                ? $this->imageUploadService->storeOptimized($uploadedFile, 'land/transactions/files', 1920, 1920, 80)
                : $this->imageUploadService->storeFile($uploadedFile, 'land/transactions/files', 'raw');

            $transaction->files()->create([
                'file_path' => $storedPath,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $mime,
                'size_bytes' => $uploadedFile->getSize(),
                'uploaded_by' => $request->user()?->id,
            ]);
        }
    }

    private function deleteStoredFile(LandTransactionFile $file): void
    {
        $resourceType = str_starts_with((string) $file->mime_type, 'image/')
            ? 'image'
            : 'raw';

        $this->imageUploadService->delete((string) $file->file_path, $resourceType);
        $file->delete();
    }

    private function remoteFileResponse(string $url, string $filename, string $mime, bool $download): Response
    {
        return RemoteMediaResponse::fromUrl(
            $url,
            $filename,
            $mime,
            $download,
            $this->cloudinaryService
        );
    }

    private function safeFilename(string $filename): string
    {
        $clean = trim(str_replace(["\r", "\n", '"'], '', $filename));

        return $clean !== '' ? $clean : 'dokumen';
    }
}
