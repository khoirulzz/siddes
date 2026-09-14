@extends('layouts.public')

@section('title', 'SID App â€“ Aplikasi Android Desa ' . config('village.name'))
@section('meta_description', 'Download Aplikasi SID (Sistem Informasi Desa) ' . config('village.name') . ' untuk Android. Akses layanan desa, surat online, PBB, pengaduan, dan asisten AI langsung dari smartphone Anda.')

@section('content')
<div class="sid-app-hero">
    <div class="container">
        <div class="sid-app-hero-inner">
            <div class="sid-app-hero-text">
                <span class="sid-app-badge">ðŸ“± Aplikasi Resmi</span>
                <h1>SID App</h1>
                <p class="sid-app-tagline">Layanan Desa {{ config('village.name') }} di Genggaman Anda</p>
                <p class="sid-app-desc">
                    Akses seluruh layanan desa â€” surat online, PBB, pengaduan warga, hingga asisten AI â€”
                    langsung dari smartphone Android Anda, kapan saja dan di mana saja.
                </p>
                <div class="sid-app-actions">
                    <a href="https://github.com/hulumzz/download/releases/SID-Mobile.apk" class="btn-download-apk" target="_blank" rel="noopener" id="btn-download-apk">
                        <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M12 16l-4-4h2.5V4h3v8H16l-4 4z" fill="currentColor"/><rect x="4" y="18" width="16" height="2" rx="1" fill="currentColor"/></svg>
                        Download APK
                        <small>Android 7.0+</small>
                    </a>
                    <div class="sid-app-version-info">
                        <span>Versi terbaru</span>
                        <span id="apk-version">â€“</span>
                    </div>
                </div>
                <p class="sid-app-disclaimer">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    Karena bukan dari Play Store, aktifkan <strong>Sumber Tidak Dikenal</strong> di Pengaturan Android sebelum menginstal.
                </p>
            </div>
            <div class="sid-app-hero-visual">
                <div class="phone-mockup">
                    <div class="phone-screen">
                        <div class="phone-screen-inner">
                            <div style="background: linear-gradient(135deg,#1a56db,#7e3af2);padding:20px 16px 12px;border-radius:0 0 24px 24px;">
                                <p style="color:rgba(255,255,255,.7);font-size:11px;margin:0">Selamat Datang,</p>
                                <p style="color:#fff;font-size:14px;font-weight:700;margin:2px 0 0">Warga Desa {{ config('village.name') }}</p>
                            </div>
                            <div style="padding:12px 12px 0;">
                                <p style="font-size:11px;font-weight:600;color:var(--text-2);margin-bottom:8px">Layanan Digital Desa</p>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                                    @foreach([['ðŸ“„','Surat Online','#1a56db'],['ðŸ ','PBB','#7e3af2'],['ðŸ“¢','Pengaduan','#ef4444'],['ðŸ¤–','AI Chat','#059669']] as [$icon,$label,$color])
                                    <div style="background:var(--surface);border-radius:12px;padding:12px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.07)">
                                        <div style="font-size:20px;margin-bottom:4px">{{ $icon }}</div>
                                        <p style="font-size:10px;font-weight:600;color:var(--text-1);margin:0">{{ $label }}</p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container sid-app-features">
    <h2>Fitur Unggulan Aplikasi</h2>
    <div class="features-grid">
        @foreach([
            ['ðŸ“„','Surat Online','Ajukan surat keterangan domisili, kematian, kelahiran, dan lainnya langsung dari HP tanpa antre.'],
            ['ðŸ ','Cek & Bayar PBB','Cari objek pajak, lihat tagihan, dan laporkan pembayaran PBB Anda dengan mudah.'],
            ['ðŸ“¢','Pengaduan Warga','Laporkan masalah infrastruktur atau layanan desa dengan foto sebagai bukti.'],
            ['ðŸ¤–','AI Asisten','Tanyakan apa saja seputar layanan desa â€” asisten AI siap membantu 24 jam.'],
            ['ðŸŽ«','Lacak Tiket','Pantau status pengajuan surat, PBB, atau laporan pengaduan menggunakan nomor tiket.'],
            ['ðŸ“°','Berita & Pengumuman','Dapatkan informasi terbaru seputar kegiatan dan pengumuman resmi desa.'],
        ] as [$icon, $title, $desc])
        <div class="feature-card">
            <div class="feature-icon">{{ $icon }}</div>
            <h3>{{ $title }}</h3>
            <p>{{ $desc }}</p>
        </div>
        @endforeach
    </div>
</div>

<div class="container sid-app-howto">
    <h2>Cara Menginstal APK</h2>
    <div class="howto-steps">
        @foreach([
            ['1','Download APK','Klik tombol "Download APK" di atas dan tunggu proses unduhan selesai.'],
            ['2','Izinkan Sumber','Buka Pengaturan â†’ Keamanan â†’ aktifkan "Instal Aplikasi Tidak Dikenal" untuk browser Anda.'],
            ['3','Instal','Buka file APK yang sudah diunduh, lalu ketuk "Instal" dan ikuti panduan.'],
            ['4','Selesai','Buka aplikasi SID dan nikmati semua layanan desa di HP Anda!'],
        ] as [$num, $title, $desc])
        <div class="howto-step">
            <div class="howto-num">{{ $num }}</div>
            <div>
                <h4>{{ $title }}</h4>
                <p>{{ $desc }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
.sid-app-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
    padding: 80px 0 60px;
    overflow: hidden;
    position: relative;
}
.sid-app-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(99,102,241,.18) 0%, transparent 60%);
    pointer-events: none;
}
.sid-app-hero-inner {
    display: flex;
    align-items: center;
    gap: 48px;
}
.sid-app-hero-text {
    flex: 1;
    min-width: 0;
}
.sid-app-badge {
    display: inline-block;
    background: rgba(99,102,241,.2);
    border: 1px solid rgba(99,102,241,.4);
    color: #a5b4fc;
    font-size: 13px;
    font-weight: 600;
    padding: 4px 14px;
    border-radius: 99px;
    margin-bottom: 16px;
}
.sid-app-hero-text h1 {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 8px;
    line-height: 1.1;
    background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.sid-app-tagline {
    font-size: 1.15rem;
    color: #c7d2fe;
    margin: 0 0 12px;
    font-weight: 500;
}
.sid-app-desc {
    font-size: 0.95rem;
    color: rgba(255,255,255,.6);
    line-height: 1.7;
    margin: 0 0 28px;
    max-width: 480px;
}
.sid-app-actions {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.btn-download-apk {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    padding: 14px 28px;
    border-radius: 14px;
    text-decoration: none;
    box-shadow: 0 4px 24px rgba(99,102,241,.4);
    transition: transform .2s, box-shadow .2s;
    flex-direction: column;
    gap: 2px;
    text-align: center;
}
.btn-download-apk svg { display: block; margin: 0 auto 2px; }
.btn-download-apk small { font-size: 11px; font-weight: 400; opacity: .7; }
.btn-download-apk:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(99,102,241,.55); }
.sid-app-version-info { color: rgba(255,255,255,.5); font-size: 12px; line-height: 1.6; }
.sid-app-version-info span { display: block; }
.sid-app-disclaimer {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    font-size: 12px;
    color: rgba(255,255,255,.45);
    line-height: 1.6;
    max-width: 440px;
}
.sid-app-disclaimer svg { flex-shrink: 0; margin-top: 2px; }

/* Phone Mockup */
.sid-app-hero-visual { flex-shrink: 0; }
.phone-mockup {
    width: 220px;
    background: #1e293b;
    border-radius: 32px;
    padding: 12px;
    box-shadow: 0 24px 64px rgba(0,0,0,.5), 0 0 0 1px rgba(255,255,255,.08);
    position: relative;
}
.phone-screen {
    background: var(--bg, #f8fafc);
    border-radius: 22px;
    overflow: hidden;
    aspect-ratio: 9/16;
    position: relative;
}
[data-theme="dark"] .phone-screen { background: #0f172a; }
.phone-screen-inner { font-family: 'Poppins', sans-serif; }

/* Features */
.sid-app-features {
    padding: 64px 0 48px;
}
.sid-app-features h2, .sid-app-howto h2 {
    font-size: 1.75rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 32px;
    color: var(--text-1);
}
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}
.feature-card {
    background: var(--surface, #fff);
    border-radius: 16px;
    padding: 24px 20px;
    box-shadow: 0 1px 8px rgba(0,0,0,.06);
    text-align: center;
    transition: transform .2s, box-shadow .2s;
    border: 1px solid var(--border, rgba(0,0,0,.06));
}
.feature-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
.feature-icon { font-size: 2rem; margin-bottom: 12px; }
.feature-card h3 { font-size: 0.95rem; font-weight: 700; margin: 0 0 8px; color: var(--text-1); }
.feature-card p { font-size: 0.82rem; color: var(--text-2); line-height: 1.6; margin: 0; }

/* How To */
.sid-app-howto {
    padding: 0 0 64px;
}
.howto-steps {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 600px;
    margin: 0 auto;
}
.howto-step {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    background: var(--surface, #fff);
    border-radius: 14px;
    padding: 20px 24px;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    border: 1px solid var(--border, rgba(0,0,0,.06));
}
.howto-num {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #6366f1, #7c3aed);
    color: #fff;
    font-size: 1rem;
    font-weight: 800;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.howto-step h4 { font-size: 0.95rem; font-weight: 700; margin: 0 0 4px; color: var(--text-1); }
.howto-step p  { font-size: 0.85rem; color: var(--text-2); margin: 0; line-height: 1.6; }

@media (max-width: 768px) {
    .sid-app-hero { padding: 48px 0 40px; }
    .sid-app-hero-inner { flex-direction: column; gap: 32px; }
    .phone-mockup { width: 180px; }
    .sid-app-hero-text h1 { font-size: 2.2rem; }
}
</style>
@endsection

