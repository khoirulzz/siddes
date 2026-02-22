@extends('layouts.public')

@section('title', $title . ' - ' . config('village.name'))

@section('content')
    <div class="section-title">
        <h2>{{ $title }}</h2>
        <span class="muted">Modul layanan tahap pengembangan</span>
    </div>

    <div class="card">
        <p class="muted">{{ $description }}</p>
        <p class="muted">Halaman sudah tersedia pada MVP 1 agar struktur menu publik lengkap sesuai blueprint.</p>
    </div>
@endsection
