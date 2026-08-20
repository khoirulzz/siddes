<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploadService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.news.index', [
            'items' => News::latest()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.news.form', [
            'item' => new News([
                'author_name' => 'Tim Desa Lambanggelun',
                'is_published' => true,
            ]),
            'method' => 'POST',
            'route' => route('dashboard.news.store'),
            'title' => 'Tambah Berita',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->makeUniqueSlug($data['title']);
        $this->storeThumbnail($request, $data);

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = Carbon::now();
        }

        News::create($data);

        return redirect()->route('dashboard.news.index')->with('success', 'Berita berhasil ditambahkan.');
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
    public function edit(News $news)
    {
        return view('dashboard.news.form', [
            'item' => $news,
            'method' => 'PUT',
            'route' => route('dashboard.news.update', $news),
            'title' => 'Edit Berita',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, News $news)
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->makeUniqueSlug($data['title'], $news->id);
        $this->storeThumbnail($request, $data, $news);

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = Carbon::now();
        }

        if (! $data['is_published']) {
            $data['published_at'] = null;
        }

        $news->update($data);

        return redirect()->route('dashboard.news.index')->with('success', 'Berita berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        $this->imageUploadService->delete((string) $news->thumbnail_path, 'image');

        $news->delete();

        return redirect()->route('dashboard.news.index')->with('success', 'Berita berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'author_name' => ['required', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ];

        $messages = [
            'thumbnail.uploaded' => 'Gagal mengunggah thumbnail. Ukuran file mungkin melebihi batas server (maksimal 10MB) atau file rusak.',
            'thumbnail.image' => 'File thumbnail harus berupa gambar.',
            'thumbnail.mimes' => 'Format thumbnail harus berupa JPG, JPEG, PNG, atau WebP.',
            'thumbnail.max' => 'Ukuran file thumbnail maksimal 10MB.',
            'title.required' => 'Judul berita wajib diisi.',
            'content.required' => 'Konten berita wajib diisi.',
            'author_name.required' => 'Nama penulis berita wajib diisi.',
        ];

        $attributes = [
            'title' => 'Judul berita',
            'excerpt' => 'Ringkasan',
            'content' => 'Konten berita',
            'thumbnail' => 'Thumbnail berita',
            'author_name' => 'Nama penulis',
            'published_at' => 'Tanggal publish',
            'is_published' => 'Status publish',
        ];

        $data = $request->validate($rules, $messages, $attributes);

        $data['is_published'] = $request->boolean('is_published');
        unset($data['thumbnail']);

        return $data;
    }

    private function storeThumbnail(Request $request, array &$data, ?News $item = null): void
    {
        if (! $request->hasFile('thumbnail')) {
            return;
        }

        if ($item) {
            $this->imageUploadService->delete((string) $item->thumbnail_path, 'image');
        }

        $data['thumbnail_path'] = $this->imageUploadService->storeOptimized(
            $request->file('thumbnail'),
            'news/thumbnails',
            1600,
            1600,
            78
        );
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (News::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}
