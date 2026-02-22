@extends('layouts.dashboard')

@section('title', $title)
@section('page_title', $title)

@section('content')
    <section class="panel">
        <h2>{{ $title }}</h2>
        <p class="muted">{{ $description }}</p>
        <p class="muted">Halaman modul sudah disiapkan agar struktur dashboard lengkap sejak MVP 1.</p>
    </section>
@endsection
