@extends('layouts.public')

@section('title', $news->title . ' - ' . config('village.name'))

@section('content')
    @php
        $newsParagraphs = preg_split('/\R{2,}/', trim((string) $news->content)) ?: [];
        if ($newsParagraphs === []) {
            $newsParagraphs = [(string) $news->content];
        }
        $shareTitle = $news->title . ' - ' . config('village.name');
        $shareUrl = request()->fullUrl();
    @endphp

    <article class="reader-article">
        @if($news->thumbnail_url)
            <img class="news-detail-thumb" src="{{ $news->thumbnail_url }}" alt="{{ $news->title }}">
        @endif

        <header class="reader-header">
            <div class="reader-kicker">Berita Desa</div>
            <h1>{{ $news->title }}</h1>
            <p class="reader-meta">
                Dipublikasikan {{ $news->published_at?->translatedFormat('d M Y') }} oleh {{ $news->author_name }}
            </p>
            @if($news->excerpt)
                <p class="reader-excerpt">{{ $news->excerpt }}</p>
            @endif
        </header>

        <div class="reader-content">
            @foreach($newsParagraphs as $paragraph)
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
    </article>

    <p style="margin-top:1rem;">
        <a class="btn btn-outline" href="{{ route('news.index') }}">Kembali ke daftar berita</a>
    </p>
@endsection

