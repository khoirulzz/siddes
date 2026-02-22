<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Services\ImageUploadService;
use App\Support\PublicMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploadService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.galleries.index', [
            'items' => Gallery::latest()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.galleries.form', [
            'item' => new Gallery(),
            'method' => 'POST',
            'route' => route('dashboard.galleries.store'),
            'title' => 'Tambah Galeri',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        $this->storeImage($request, $data);

        Gallery::create($data);

        return redirect()->route('dashboard.galleries.index')->with('success', 'Data galeri berhasil ditambahkan.');
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
    public function edit(Gallery $gallery)
    {
        return view('dashboard.galleries.form', [
            'item' => $gallery,
            'method' => 'PUT',
            'route' => route('dashboard.galleries.update', $gallery),
            'title' => 'Edit Galeri',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gallery $gallery)
    {
        $data = $this->validatePayload($request, $gallery);
        $this->storeImage($request, $data, $gallery);

        $gallery->update($data);

        return redirect()->route('dashboard.galleries.index')->with('success', 'Data galeri berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gallery $gallery)
    {
        $existingPath = PublicMedia::normalizePath((string) $gallery->getRawOriginal('image_url'));
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        $gallery->delete();

        return redirect()->route('dashboard.galleries.index')->with('success', 'Data galeri berhasil dihapus.');
    }

    private function validatePayload(Request $request, ?Gallery $gallery = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'image' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:max_width=7000,max_height=7000',
                'max:5120',
            ],
            'description' => ['nullable', 'string'],
            'activity_date' => ['nullable', 'date'],
        ];

        if (! $gallery) {
            $rules['image'][0] = 'required';
        }

        $data = $request->validate($rules);
        unset($data['image']);

        return $data;
    }

    private function storeImage(Request $request, array &$data, ?Gallery $item = null): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $existingPath = $item ? PublicMedia::normalizePath((string) $item->getRawOriginal('image_url')) : null;
        if ($existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        $data['image_url'] = $this->imageUploadService->storeOptimized(
            $request->file('image'),
            'galleries/photos',
            1920,
            1920,
            78
        );
    }
}
