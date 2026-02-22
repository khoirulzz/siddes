@extends('layouts.public')

@section('title', 'Pengumuman - ' . config('village.name'))

@section('content')
    <div class="section-title">
        <h2>Pengumuman Desa</h2>
        <span class="muted">Informasi resmi pemerintah desa</span>
    </div>

    <div class="list">
        @forelse ($announcements as $item)
            <article class="list-item announcement-list-item">
                <img class="announcement-thumb" src="{{ $item->thumbnail_url }}" alt="Ikon pengumuman">
                <div>
                    <h3>
                        <a href="{{ route('announcements.show', $item) }}">{{ $item->title }}</a>
                    </h3>
                    <p>{{ \Illuminate\Support\Str::limit($item->content, 220) }}</p>
                    @if($item->start_date || $item->end_date)
                        <p class="muted" style="margin-top:0.55rem;">
                            Berlaku: {{ $item->start_date?->translatedFormat('d M Y') ?? '-' }}
                            @if($item->end_date)
                                s/d {{ $item->end_date->translatedFormat('d M Y') }}
                            @endif
                        </p>
                    @endif
                    <div class="announcement-actions">
                        <a class="announcement-readmore" href="{{ route('announcements.show', $item) }}">Baca selengkapnya</a>
                        @if($item->link_url)
                            <a class="announcement-readmore external" href="{{ $item->link_url }}" target="_blank" rel="noopener">Buka link terkait</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <article class="list-item">
                <p>Belum ada pengumuman aktif.</p>
            </article>
        @endforelse
    </div>

    @if($announcements->hasPages())
        <div class="list-pagination">
            <div class="pager-controls">
                @if($announcements->onFirstPage())
                    <span class="pager-link is-disabled">Sebelumnya</span>
                @else
                    <a class="pager-link" href="{{ $announcements->previousPageUrl() }}">Sebelumnya</a>
                @endif

                <span class="pager-meta">Halaman {{ $announcements->currentPage() }} / {{ $announcements->lastPage() }}</span>

                @if($announcements->hasMorePages())
                    <a class="pager-link" href="{{ $announcements->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="pager-link is-disabled">Berikutnya</span>
                @endif
            </div>
        </div>
    @endif
@endsection
