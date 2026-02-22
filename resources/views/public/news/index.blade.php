@extends('layouts.public')

@section('title', 'Berita Desa - ' . config('village.name'))

@section('content')
    <div class="section-title">
        <h2>Berita Desa</h2>
        <span class="muted">Publikasi kegiatan dan informasi resmi desa</span>
    </div>

    <div class="list">
        @forelse ($news as $item)
            <article class="list-item news-list-item">
                @if($item->thumbnail_url)
                    <img class="news-list-thumb" src="{{ $item->thumbnail_url }}" alt="{{ $item->title }}">
                @endif
                <div>
                    <h3><a href="{{ route('news.show', $item) }}">{{ $item->title }}</a></h3>
                    <p class="muted" style="margin-bottom:0.45rem;">
                        {{ $item->author_name }} - {{ $item->published_at?->translatedFormat('d M Y') }}
                    </p>
                    <p>{{ $item->excerpt }}</p>
                </div>
            </article>
        @empty
            <article class="list-item">
                <p>Belum ada berita yang dipublikasikan.</p>
            </article>
        @endforelse
    </div>

    @if($news->hasPages())
        <div class="list-pagination">
            <div class="pager-controls">
                @if($news->onFirstPage())
                    <span class="pager-link is-disabled">Sebelumnya</span>
                @else
                    <a class="pager-link" href="{{ $news->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span class="pager-meta">Halaman {{ $news->currentPage() }} / {{ $news->lastPage() }}</span>

                @if($news->hasMorePages())
                    <a class="pager-link" href="{{ $news->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="pager-link is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    @endif
@endsection
