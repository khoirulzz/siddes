@extends('layouts.dashboard')

@section('title', 'Manajemen Galeri')
@section('page_title', 'Manajemen Galeri')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Daftar Galeri</h2>
            <a class="btn btn-primary" href="{{ route('dashboard.galleries.create') }}">Tambah Galeri</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Gambar</th>
                    <th>Tanggal Kegiatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td><a href="{{ $item->image_url }}" target="_blank">Lihat gambar</a></td>
                        <td>{{ $item->activity_date?->format('d-m-Y') ?? '-' }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary" href="{{ route('dashboard.galleries.edit', $item) }}">Edit</a>
                                <form action="{{ route('dashboard.galleries.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data galeri ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada data galeri.</td></tr>
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
