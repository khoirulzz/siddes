@extends('layouts.public')

@section('title', $announcement->title . ' - ' . config('village.name'))

@section('content')
    @php
        $announcementParagraphs = preg_split('/\R{2,}/', trim((string) $announcement->content)) ?: [];
        if ($announcementParagraphs === []) {
            $announcementParagraphs = [(string) $announcement->content];
        }
        $shareTitle = $announcement->title . ' - ' . config('village.name');
        $shareUrl = request()->fullUrl();
    @endphp

    <article class="reader-article">
        <header class="reader-header">
            <div class="reader-kicker">Pengumuman Resmi</div>
            <h1>{{ $announcement->title }}</h1>
            <p class="reader-meta">
                Dipublikasikan {{ $announcement->created_at?->translatedFormat('d M Y') }}
                @if($announcement->start_date || $announcement->end_date)
                    &middot; Berlaku {{ $announcement->start_date?->translatedFormat('d M Y') ?? '-' }}
                    @if($announcement->end_date)
                        s/d {{ $announcement->end_date->translatedFormat('d M Y') }}
                    @endif
                @endif
            </p>
        </header>

        <div class="reader-content">
            @foreach($announcementParagraphs as $paragraph)
                @if(trim($paragraph) !== '')
                    <p>{!! nl2br(e(trim($paragraph))) !!}</p>
                @endif
            @endforeach
        </div>

        @include('public.partials.share-actions', [
            'shareTitle' => $shareTitle,
            'shareUrl' => $shareUrl,
            'shareLabel' => 'Share with',
        ])

        @if($announcement->link_url)
            <div class="announcement-detail-link">
                <a class="btn btn-primary" href="{{ $announcement->link_url }}" target="_blank" rel="noopener">Buka Link Terkait</a>
            </div>
        @endif
    </article>

    <p style="margin-top:1rem;">
        <a class="btn btn-outline" href="{{ route('announcements.index') }}">Kembali ke daftar pengumuman</a>
    </p>
@endsection

