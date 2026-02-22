<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.announcements.index', [
            'items' => Announcement::latest()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.announcements.form', [
            'item' => new Announcement(['is_active' => true]),
            'method' => 'POST',
            'route' => route('dashboard.announcements.store'),
            'title' => 'Tambah Pengumuman',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Announcement::create($this->validatePayload($request));

        return redirect()->route('dashboard.announcements.index')->with('success', 'Pengumuman berhasil ditambahkan.');
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
    public function edit(Announcement $announcement)
    {
        return view('dashboard.announcements.form', [
            'item' => $announcement,
            'method' => 'PUT',
            'route' => route('dashboard.announcements.update', $announcement),
            'title' => 'Edit Pengumuman',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validatePayload($request));

        return redirect()->route('dashboard.announcements.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('dashboard.announcements.index')->with('success', 'Pengumuman berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
