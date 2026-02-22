@extends('layouts.public')

@section('title', 'Gallery - ' . config('village.name'))

@section('content')
    <div class="section-title">
        <h2>Gallery Kegiatan Desa</h2>
        <span class="muted">Dokumentasi kegiatan dan aktivitas desa</span>
    </div>

    <div class="gallery-grid">
        @forelse ($galleries as $item)
            <article class="gallery-item">
                <img src="{{ $item->image_url }}" alt="{{ $item->title }}">
                <div class="meta">
                    <strong>{{ $item->title }}</strong>
                    <p class="muted">{{ $item->description }}</p>
                    <p class="muted">{{ $item->activity_date?->translatedFormat('d M Y') ?? '-' }}</p>
                </div>
            </article>
        @empty
            <article class="list-item">
                <p>Belum ada data galeri.</p>
            </article>
        @endforelse
    </div>

    @if($galleries->hasPages())
        <div class="list-pagination">
            <div class="pager-controls">
                @if($galleries->onFirstPage())
                    <span class="pager-link is-disabled">Sebelumnya</span>
                @else
                    <a class="pager-link" href="{{ $galleries->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span class="pager-meta">Halaman {{ $galleries->currentPage() }} / {{ $galleries->lastPage() }}</span>

                @if($galleries->hasMorePages())
                    <a class="pager-link" href="{{ $galleries->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="pager-link is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    @endif
@endsection
