@extends('layouts.public')
@section('title', 'Download SID App - ' . config('village.name'))
@section('meta_description', 'Akses layanan Desa ' . config('village.name') . ' dari smartphone Anda. Download aplikasi resmi sekarang.')

@section('content')
<div class="sid-app-wrapper">
    <!-- Hero Section -->
    <div class="sid-app-hero">
        <div class="container sid-app-hero-inner">
            <div class="sid-app-hero-text">
                <span class="sid-app-badge">Aplikasi Resmi Desa</span>
                <h1>SID App</h1>
                <p class="sid-app-tagline">Layanan Desa {{ config('village.name') }} di Genggaman Anda</p>
                <p class="sid-app-desc">Akses seluruh layanan desa &mdash; mulai dari surat online, PBB, pengaduan warga, hingga asisten AI &mdash; langsung dari smartphone Android Anda, kapan saja dan di mana saja.</p>
                
                <div class="sid-app-actions">
                    <a href="https://github.com/hulumzz/download/releases/SID-Mobile.apk" class="btn-download-apk" target="_blank" rel="noopener">
                        <i class="bi bi-android2 fs-3"></i>
                        <span class="fw-bold">Download APK</span>
                        <small>Android 7.0+</small>
                    </a>
                    
                    <div class="sid-app-disclaimer">
                        <i class="bi bi-info-circle"></i>
                        <span>Karena bukan dari Play Store, aktifkan <strong>Sumber Tidak Dikenal</strong> di Pengaturan Android sebelum menginstal.</span>
                    </div>
                </div>
            </div>
            
            <div class="sid-app-hero-visual">
                <div class="phone-mockup animate__animated animate__fadeInUp">
                    <div class="phone-screen">
                        <!-- Simulated App UI inside Phone -->
                        <div class="phone-screen-inner" style="background:#0f172a; height:100%; padding:15px; color:#fff">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px">
                                <div>
                                    <div style="font-size:11px; opacity:0.7">Halo, Selamat Datang</div>
                                    <div style="font-size:14px; font-weight:700">Warga Desa</div>
                                </div>
                                <div style="width:32px; height:32px; border-radius:50%; background:#334155"></div>
                            </div>
                            
                            <div style="background: linear-gradient(135deg, #6366f1, #7c3aed); border-radius:12px; padding:15px; margin-bottom:20px">
                                <div style="font-size:12px; opacity:0.9; margin-bottom:5px">Pusat Layanan</div>
                                <div style="font-size:16px; font-weight:700">Layanan Desa Online</div>
                            </div>
                            
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
                                @foreach([
                                    ['bi-envelope-paper', 'Surat Online'],
                                    ['bi-receipt', 'Cek PBB'],
                                    ['bi-chat-dots', 'Asisten AI'],
                                    ['bi-megaphone', 'Pengaduan']
                                ] as [$icon, $label])
                                <div style="background:#1e293b; border-radius:12px; padding:15px; text-align:center">
                                    <i class="bi {{ $icon }} text-indigo-400" style="font-size:24px; color:#818cf8; margin-bottom:8px; display:block"></i>
                                    <div style="font-size:11px; font-weight:500">{{ $label }}</div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="container sid-app-features py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Fitur Unggulan Aplikasi</h2>
            <p class="text-muted">Semua yang Anda butuhkan dalam satu aplikasi.</p>
        </div>
        
        <div class="row g-4">
            @foreach([
                ['bi-envelope-plus', 'Surat Online', 'Ajukan surat keterangan domisili, kematian, kelahiran, dan lainnya langsung dari HP tanpa perlu antre ke balai desa.'],
                ['bi-receipt', 'Cek & Bayar PBB', 'Cari objek pajak, lihat tagihan tahunan, dan laporkan bukti pembayaran PBB Anda dengan mudah.'],
                ['bi-exclamation-triangle', 'Pengaduan Warga', 'Laporkan masalah infrastruktur, lingkungan, atau layanan desa dengan foto dan lokasi sebagai bukti otentik.'],
                ['bi-robot', 'AI Asisten', 'Tanyakan apa saja seputar layanan, syarat administrasi, atau info desa &mdash; asisten AI siap membantu 24 jam.'],
                ['bi-search', 'Lacak Tiket', 'Pantau status pemrosesan pengajuan surat, PBB, atau laporan pengaduan secara real-time menggunakan nomor tiket.'],
                ['bi-newspaper', 'Berita & Pengumuman', 'Dapatkan notifikasi dan informasi terbaru seputar kegiatan, program bansos, dan pengumuman resmi desa.']
            ] as [$icon, $title, $desc])
            <div class="col-md-6 col-lg-4">
                <div class="feature-card h-100">
                    <div class="feature-icon"><i class="bi {{ $icon }}"></i></div>
                    <h4 class="fw-bold fs-5 mb-2">{{ $title }}</h4>
                    <p class="text-muted mb-0 small">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- How To Install Section -->
    <div class="sid-app-howto bg-light py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Cara Menginstal APK</h2>
                <p class="text-muted">Ikuti langkah mudah berikut untuk menggunakan aplikasi SID</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="howto-steps">
                        @foreach([
                            ['1', 'Download APK', 'Klik tombol "Download APK" di atas dan tunggu hingga proses unduhan file selesai ke penyimpanan HP Anda.'],
                            ['2', 'Izinkan Sumber', 'Buka <strong>Pengaturan &gt; Keamanan</strong> (atau Privasi), lalu aktifkan opsi <strong>"Instal Aplikasi Tidak Dikenal"</strong> untuk browser atau file manager Anda.'],
                            ['3', 'Instal Aplikasi', 'Buka notifikasi unduhan atau folder Download, ketuk file <strong>SID-Mobile.apk</strong>, lalu pilih <strong>"Instal"</strong>.'],
                            ['4', 'Selesai', 'Buka aplikasi SID yang telah terinstal. Selamat! Anda sekarang dapat menikmati semua layanan desa di HP Anda.']
                        ] as [$num, $title, $desc])
                        <div class="howto-step">
                            <div class="howto-num">{{ $num }}</div>
                            <div class="howto-content">
                                <h5 class="fw-bold mb-1">{{ $title }}</h5>
                                <p class="text-muted small mb-0">{!! $desc !!}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Base Styles */
.sid-app-wrapper { font-family: 'Poppins', sans-serif; }
.text-indigo-400 { color: #818cf8 !important; }

/* Hero Section */
.sid-app-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}
.sid-app-hero::before {
    content: ''; position: absolute; inset: 0;
    background: radial-gradient(circle at 80% 50%, rgba(99,102,241,0.15), transparent 50%);
    pointer-events: none;
}
.sid-app-hero-inner {
    display: flex; align-items: center; justify-content: space-between; gap: 40px;
    position: relative; z-index: 1;
}
.sid-app-hero-text { flex: 1; max-width: 600px; }
.sid-app-badge {
    display: inline-block; background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.3);
    color: #a5b4fc; font-size: 13px; font-weight: 600; padding: 6px 16px; border-radius: 50px;
    margin-bottom: 20px;
}
.sid-app-hero-text h1 {
    font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 800; margin-bottom: 10px;
    background: linear-gradient(to right, #ffffff, #a5b4fc);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1.1;
}
.sid-app-tagline { font-size: 1.25rem; color: #e0e7ff; font-weight: 500; margin-bottom: 16px; }
.sid-app-desc { font-size: 1rem; color: rgba(255,255,255,0.7); line-height: 1.6; margin-bottom: 32px; }

/* Download Button */
.sid-app-actions { display: flex; flex-direction: column; align-items: flex-start; gap: 16px; }
.btn-download-apk {
    display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #6366f1, #7c3aed); color: #fff;
    padding: 12px 32px; border-radius: 12px; text-decoration: none;
    box-shadow: 0 4px 20px rgba(99,102,241,0.4); transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);
}
.btn-download-apk:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(99,102,241,0.6); color: #fff; }
.btn-download-apk small { font-size: 11px; opacity: 0.8; margin-top: 2px; }

.sid-app-disclaimer {
    display: flex; gap: 10px; color: rgba(255,255,255,0.5); font-size: 12px; max-width: 400px; line-height: 1.5;
}
.sid-app-disclaimer i { font-size: 16px; margin-top: 2px; }

/* Phone Visual */
.sid-app-hero-visual { flex-shrink: 0; perspective: 1000px; }
.phone-mockup {
    width: 260px; background: #1e293b; border-radius: 40px; padding: 12px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.1);
    transform: rotateY(-15deg) rotateX(5deg); transition: transform 0.5s ease;
}
.phone-mockup:hover { transform: rotateY(0) rotateX(0); }
.phone-screen {
    background: #0f172a; border-radius: 28px; overflow: hidden; height: 550px; position: relative;
    border: 1px solid rgba(255,255,255,0.05);
}

/* Feature Cards */
.feature-card {
    background: #fff; border-radius: 16px; padding: 24px; border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; text-align: left;
}
[data-theme="dark"] .feature-card { background: #1e293b; border-color: #334155; }
.feature-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
.feature-icon {
    width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.1);
    color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 24px;
    margin-bottom: 20px;
}

/* How to Steps */
.howto-steps { display: flex; flex-direction: column; gap: 20px; }
.howto-step {
    display: flex; gap: 20px; background: #fff; padding: 24px; border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid rgba(0,0,0,0.05); align-items: center;
}
[data-theme="dark"] .howto-step, [data-theme="dark"] .sid-app-howto.bg-light { background: #0f172a !important; }
[data-theme="dark"] .howto-step { border-color: #1e293b; background: #1e293b !important; }
.howto-num {
    width: 48px; height: 48px; flex-shrink: 0; background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff; font-size: 20px; font-weight: bold; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(99,102,241,0.3);
}

/* Responsive */
@media (max-width: 991px) {
    .sid-app-hero-inner { flex-direction: column; text-align: center; }
    .sid-app-hero-text { max-width: 100%; display: flex; flex-direction: column; align-items: center; }
    .sid-app-actions { align-items: center; }
    .sid-app-disclaimer { text-align: left; }
    .phone-mockup { transform: rotateY(0) rotateX(0); width: 240px; margin-top: 30px; }
    .phone-screen { height: 500px; }
}
@media (max-width: 576px) {
    .sid-app-hero { padding: 50px 0; }
    .feature-card, .howto-step { padding: 20px; }
    .howto-step { flex-direction: column; gap: 15px; text-align: center; }
    .howto-num { margin: 0 auto; }
}
</style>
@endsection
