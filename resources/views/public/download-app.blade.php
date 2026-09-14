@extends('layouts.public')

@section('title', 'Unduh Aplikasi SID - ' . config('village.name'))
@section('meta_description', 'Unduh aplikasi SID untuk mengakses layanan dan informasi ' . config('village.name') . ' melalui ponsel Android. Lihat fitur dan panduan instalasinya di sini.')

@section('content')
<div class="sid-download">
    <nav class="sid-download-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">Beranda</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">Unduh Aplikasi</span>
    </nav>

    <section class="sid-download-hero" aria-labelledby="download-title">
        <div class="sid-download-intro">
            <span class="sid-download-eyebrow"><i class="bi bi-phone" aria-hidden="true"></i> Aplikasi resmi desa</span>
            <h1 id="download-title">Layanan desa,<br>lebih dekat dengan Anda.</h1>
            <p>Akses layanan dan informasi {{ config('village.name') }} melalui aplikasi SID. Urus pengajuan, sampaikan laporan, dan ikuti kabar desa dari ponsel Anda.</p>
            <a class="sid-download-guide-link" href="#panduan-instalasi">Lihat cara memasang aplikasi <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
        </div>

        <aside class="sid-download-card" aria-labelledby="app-title">
            <div class="sid-download-app-brand">
                <span class="sid-download-app-logo"><img src="{{ config('village.logo_url') }}" alt="" width="48" height="56"></span>
                <div>
                    <h2 id="app-title">SID Mobile</h2>
                    <p>{{ config('village.name') }}</p>
                </div>
            </div>
            <p class="sid-download-card-copy">Satu aplikasi untuk tetap terhubung dengan layanan desa Anda.</p>
            <div class="sid-download-specs">
                <span><i class="bi bi-android2" aria-hidden="true"></i> Android 8.0 ke atas</span>
                <span>Format APK</span>
            </div>
            <a href="https://github.com/hulumzz/download/releases/SID-Mobile.apk" class="btn btn-primary sid-download-button" target="_blank" rel="noopener">
                <i class="bi bi-download" aria-hidden="true"></i> Unduh aplikasi Android
                <span class="sid-download-sr-only"> (dibuka di tab baru)</span>
            </a>
            <p class="sid-download-file-note">File APK diunduh melalui GitHub.</p>
            <div class="sid-download-install-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <p>Aplikasi dipasang melalui file APK. Ikuti <a href="#panduan-instalasi">panduan instalasi</a> di bawah saat pertama kali memasang.</p>
            </div>
        </aside>
    </section>

    <section class="sid-download-section" aria-labelledby="download-features-title">
        <div class="section-title">
            <h2 id="download-features-title">Kebutuhan warga, dalam satu aplikasi</h2>
            <span class="muted">Layanan yang dekat dengan keseharian Anda</span>
        </div>
        <div class="sid-download-features">
            @foreach([
                ['bi-envelope-paper', 'Surat online', 'Ajukan surat keperluan administrasi tanpa harus datang untuk memulai pengajuan.'],
                ['bi-receipt', 'Layanan PBB', 'Lihat tagihan pajak dan kirim bukti pembayaran PBB Anda.'],
                ['bi-megaphone', 'Pengaduan warga', 'Sampaikan keluhan atau masalah di lingkungan Anda kepada pemerintah desa.'],
                ['bi-chat-dots', 'Asisten desa', 'Temukan informasi tentang layanan desa dan persyaratan administrasi.'],
                ['bi-search', 'Lacak pengajuan', 'Periksa perkembangan layanan yang Anda ajukan menggunakan nomor tiket.'],
                ['bi-newspaper', 'Kabar desa', 'Ikuti berita kegiatan dan pengumuman terbaru dari pemerintah desa.']
            ] as [$icon, $title, $description])
                <article class="sid-download-feature">
                    <span class="sid-download-feature-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                    <div>
                        <h3>{{ $title }}</h3>
                        <p>{{ $description }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="panduan-instalasi" class="sid-download-section sid-download-install" aria-labelledby="download-install-title">
        <div class="sid-download-install-heading">
            <span class="sid-download-eyebrow">Panduan singkat</span>
            <h2 id="download-install-title">Siap digunakan dalam beberapa langkah</h2>
            <p>Siapkan ponsel Android Anda, lalu ikuti petunjuk berikut.</p>
            <div class="sid-download-web-option">
                <i class="bi bi-globe2" aria-hidden="true"></i>
                <p>Memakai iPhone atau ingin akses lewat browser? Layanan desa juga tersedia di website.</p>
                <a class="btn btn-outline" href="{{ route('home') }}">Buka website SID <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
        <ol class="sid-download-steps">
            <li>
                <h3>Unduh file aplikasi</h3>
                <p>Ketuk <strong>Unduh aplikasi Android</strong> dan tunggu hingga file <strong>SID-Mobile.apk</strong> selesai diunduh.</p>
            </li>
            <li>
                <h3>Buka file dan berikan izin</h3>
                <p>Buka file dari folder Unduhan. Jika diminta, buka Setelan dan izinkan <strong>Instal aplikasi tidak dikenal</strong> untuk browser atau pengelola file yang Anda gunakan.</p>
            </li>
            <li>
                <h3>Pasang, lalu buka SID</h3>
                <p>Kembali ke file APK, pilih <strong>Instal</strong>, lalu buka aplikasi setelah selesai. Izin instalasi tadi dapat dinonaktifkan kembali.</p>
            </li>
        </ol>
    </section>
</div>
@endsection
