@extends('layouts.dashboard')

@section('title', 'Manajemen Pengumuman')
@section('page_title', 'Manajemen Pengumuman')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Daftar Pengumuman</h2>
            <a class="btn btn-primary" href="{{ route('dashboard.announcements.create') }}">Tambah Pengumuman</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Periode</th>
                    <th>Link</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td>
                            @if($item->start_date || $item->end_date)
                                {{ $item->start_date?->format('d-m-Y') ?? '-' }}
                                @if($item->end_date)
                                    s/d {{ $item->end_date->format('d-m-Y') }}
                                @endif
                            @else
                                Tanpa tenggat
                            @endif
                        </td>
                        <td>
                            @if($item->link_url)
                                <a href="{{ $item->link_url }}" target="_blank" rel="noopener">Buka link</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary" href="{{ route('dashboard.announcements.edit', $item) }}">Edit</a>
                                <form action="{{ route('dashboard.announcements.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada data pengumuman.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if($items->hasPages())
            <div class="table-pagination" style="margin-top:0.8rem;">
                <small class="muted">
                    Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
                </small>
                <div class="pager-controls">
                    @if($items->onFirstPage())
                        <span class="pager-link is-disabled">Sebelumnya</span>
                    @else
                        <a class="pager-link" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    <span class="pager-meta">Halaman {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>

                    @if($items->hasMorePages())
                        <a class="pager-link" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="pager-link is-disabled">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
