@extends('layouts.dashboard')

@section('title', 'Manajemen Berita')
@section('page_title', 'Manajemen Berita')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Daftar Berita</h2>
            <a class="btn btn-primary" href="{{ route('dashboard.news.create') }}">Tambah Berita</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>Tanggal Publish</th>
                    <th>Penulis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            @if($item->thumbnail_url)
                                <a href="{{ $item->thumbnail_url }}" target="_blank">Lihat</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->title }}</td>
                        <td>{{ $item->is_published ? 'Publish' : 'Draft' }}</td>
                        <td>{{ $item->published_at?->format('d-m-Y H:i') ?? '-' }}</td>
                        <td>{{ $item->author_name }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary" href="{{ route('dashboard.news.edit', $item) }}">Edit</a>
                                <form action="{{ route('dashboard.news.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus berita ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada data berita.</td></tr>
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
