<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('village.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('css/public-service-form.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container site-header-inner">
            <a href="{{ route('home') }}" class="brand">
                <img src="{{ config('village.logo_url') }}" alt="Logo {{ config('village.district') }}">
                <span>
                    {{ config('village.name') }}
                    <small>{{ config('village.district') }}</small>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle>Menu</button>

            <nav class="main-nav" data-main-nav>
                <ul class="menu">
                    <li><a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a></li>
                    <li class="has-dropdown" data-dropdown>
                        <button type="button" class="{{ request()->routeIs('profile') ? 'active' : '' }}">Profil Desa</button>
                        <div class="dropdown">
                            <a href="{{ route('profile') }}#profil-singkat">Profil Singkat</a>
                            <a href="{{ route('profile') }}#visi-misi">Visi Misi</a>
                            <a href="{{ route('profile') }}#struktur-organisasi">Struktur Organisasi</a>
                            <a href="{{ route('profile') }}#perangkat-desa">Data Perangkat Desa</a>
                            <a href="{{ route('profile') }}#batas-wilayah">Batas Wilayah</a>
                            <a href="{{ route('profile') }}#peta-desa">Peta</a>
                        </div>
                    </li>

                    <li class="has-dropdown" data-dropdown>
                        <button type="button">Informasi Publik</button>
                        <div class="dropdown">
                            <a class="{{ request()->routeIs('information.population') ? 'active' : '' }}" href="{{ route('information.population') }}">Kependudukan</a>
                            <a class="{{ request()->routeIs('information.land') ? 'active' : '' }}" href="{{ route('information.land') }}">Pertanahan</a>
                            <a class="{{ request()->routeIs('information.activities') ? 'active' : '' }}" href="{{ route('information.activities') }}">Kegiatan Desa</a>
                        </div>
                    </li>

                    <li class="has-dropdown" data-dropdown>
                        <button type="button">Layanan</button>
                        <div class="dropdown">
                            <a class="{{ request()->routeIs('services.pbb') ? 'active' : '' }}" href="{{ route('services.pbb') }}">PBB</a>
                            <a class="{{ request()->routeIs('services.letter') ? 'active' : '' }}" href="{{ route('services.letter') }}">Surat Online</a>
                            <a class="{{ request()->routeIs('services.complaint') ? 'active' : '' }}" href="{{ route('services.complaint') }}">Pengaduan</a>
                        </div>
                    </li>

                    <li class="has-dropdown" data-dropdown>
                        <button type="button" class="{{ request()->routeIs('announcements.index') || request()->routeIs('announcements.show') || request()->routeIs('news.index') || request()->routeIs('news.show') || request()->routeIs('gallery.index') ? 'active' : '' }}">
                            Pengumuman
                        </button>
                        <div class="dropdown">
                            <a class="{{ request()->routeIs('announcements.index') || request()->routeIs('announcements.show') ? 'active' : '' }}" href="{{ route('announcements.index') }}">Pengumuman</a>
                            <a class="{{ request()->routeIs('news.index') || request()->routeIs('news.show') ? 'active' : '' }}" href="{{ route('news.index') }}">Berita</a>
                            <a class="{{ request()->routeIs('gallery.index') ? 'active' : '' }}" href="{{ route('gallery.index') }}">Galeri</a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="header-actions">
                @auth
                    <a class="btn btn-outline" href="{{ route('dashboard.index') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Keluar</button>
                    </form>
                @else
                    <a class="btn btn-primary" href="{{ route('login') }}">Login Admin</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="page">
        <div class="container">
            @include('partials.flash')
            @yield('content')
        </div>
    </main>

    @php
        $phone = (string) config('village.phone');
        $phoneDial = preg_replace('/[^0-9+]/', '', $phone);
        $mapLink = config('village.map_link_url', str_replace('&output=embed', '', (string) config('village.map_embed_url')));
    @endphp
    <footer class="site-footer">
        <div class="container footer-shell">
            <div class="footer-grid">
                <section class="footer-brand-block">
                    <div class="footer-brand-header">
                        <img src="{{ config('village.logo_url') }}" alt="Logo {{ config('village.name') }}">
                        <div>
                            <h3>{{ config('village.name') }}</h3>
                            <p>Portal Informasi Resmi Pemerintah Desa {{ config('village.district') }}</p>
                        </div>
                    </div>
                    <p class="footer-brand-desc">
                        Platform layanan dan informasi publik desa untuk mendukung transparansi,
                        partisipasi warga, dan akses layanan yang lebih cepat.
                    </p>
                    <p class="footer-address-chip">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6" stroke="currentColor" stroke-width="1.7"/></svg>
                        </span>
                        <span>{{ config('village.address') }}</span>
                    </p>
                </section>

                <section class="footer-nav-block">
                    <h4>Navigasi Cepat</h4>
                    <div class="footer-link-grid">
                        <a href="{{ route('home') }}">Beranda</a>
                        <a href="{{ route('profile') }}">Profil Desa</a>
                        <a href="{{ route('information.population') }}">Data Publik</a>
                        <a href="{{ route('services.letter') }}">Layanan Surat</a>
                        <a href="{{ route('news.index') }}">Berita Desa</a>
                        <a href="{{ route('gallery.index') }}">Galeri</a>
                    </div>
                </section>

                <section class="footer-contact-block">
                    <h4>Kontak Resmi</h4>
                    <a class="footer-contact-item" href="tel:{{ $phoneDial }}">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M22 16.92v2a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 3.2 2 2 0 0 1 4.06 1h2a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.64a2 2 0 0 1-.45 2.1L7.38 8.3a16 16 0 0 0 8.32 8.32l.84-.84a2 2 0 0 1 2.1-.45c.86.29 1.74.5 2.64.62A2 2 0 0 1 22 16.92Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Telepon Kantor</small>
                            <strong>{{ $phone }}</strong>
                        </span>
                    </a>
                    <a class="footer-contact-item" href="mailto:{{ config('village.email') }}">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M3 8l9 6 9-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Email Resmi</small>
                            <strong>{{ config('village.email') }}</strong>
                        </span>
                    </a>
                    <a class="footer-contact-item" href="{{ $mapLink }}" target="_blank" rel="noopener">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M3 6.5 9 4l6 2.5L21 4v13.5L15 20l-6-2.5L3 20V6.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 4v13.5M15 6.5V20" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Lokasi Kantor Desa</small>
                            <strong>Lihat di Google Maps</strong>
                        </span>
                    </a>
                </section>

                <section class="footer-social-block">
                    <h4>Media Sosial Desa</h4>
                    <a class="footer-social-link social-instagram" href="{{ config('village.instagram_url') }}" target="_blank" rel="noopener">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="4.2" stroke="currentColor" stroke-width="1.7"/><circle cx="17.3" cy="6.7" r="1.1" fill="currentColor"/></svg>
                        </span>
                        <span>
                            <small>Instagram</small>
                            <strong>@pemdes_lambanggelun</strong>
                        </span>
                    </a>
                    <a class="footer-social-link social-facebook" href="{{ config('village.facebook_url') }}" target="_blank" rel="noopener">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M14 8h3V4h-3a5 5 0 0 0-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <small>Facebook</small>
                            <strong>Desa Lambanggelun</strong>
                        </span>
                    </a>
                    <a class="footer-social-link social-maps" href="{{ $mapLink }}" target="_blank" rel="noopener">
                        <span class="footer-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6" stroke="currentColor" stroke-width="1.7"/></svg>
                        </span>
                        <span>
                            <small>Google Maps</small>
                            <strong>Lokasi Desa</strong>
                        </span>
                    </a>
                </section>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ config('village.name') }}. Developed by {{ config('village.developed_by') }}.</p>
                <p>Sistem informasi dan layanan digital desa.</p>
            </div>
        </div>
    </footer>

    <script>
        const navToggle = document.querySelector('[data-nav-toggle]');
        const mainNav = document.querySelector('[data-main-nav]');
        const dropdowns = document.querySelectorAll('[data-dropdown] > button');

        if (navToggle && mainNav) {
            navToggle.addEventListener('click', () => {
                mainNav.classList.toggle('show');
            });
        }

        dropdowns.forEach((button) => {
            button.addEventListener('click', () => {
                if (window.innerWidth <= 920) {
                    button.parentElement.classList.toggle('open');
                }
            });
        });

        const showCopyToast = (message) => {
            const existing = document.querySelector('.copy-toast');
            if (existing) {
                existing.remove();
            }

            const toast = document.createElement('div');
            toast.className = 'copy-toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            window.setTimeout(() => toast.remove(), 1800);
        };

        const copyText = async (text) => {
            if (!text) {
                return false;
            }

            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return true;
            }

            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            const copied = document.execCommand('copy');
            textArea.remove();
            return copied;
        };

        document.addEventListener('click', async (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const button = target.closest('[data-copy-value]');
            if (!(button instanceof HTMLElement)) {
                return;
            }

            const value = button.getAttribute('data-copy-value') || '';
            if (!value.trim()) {
                showCopyToast('Tidak ada teks untuk disalin.');
                return;
            }

            try {
                const copied = await copyText(value.trim());
                showCopyToast(copied ? 'Berhasil disalin.' : 'Gagal menyalin. Coba lagi.');
            } catch (error) {
                showCopyToast('Gagal menyalin. Coba lagi.');
            }
        });
    </script>
    <!-- Bootstrap JS (bundle includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle collapse icon (+ / -) for our custom collapse headers
        document.querySelectorAll('.collapse-header').forEach(header => {
            const targetSelector = header.getAttribute('data-bs-target');
            if (!targetSelector) return;
            const target = document.querySelector(targetSelector);
            const icon = header.querySelector('.collapse-icon');
            if (!target) return;

            target.addEventListener('show.bs.collapse', () => { if (icon) icon.textContent = '-'; });
            target.addEventListener('hide.bs.collapse', () => { if (icon) icon.textContent = '+'; });

            // allow clicking header to toggle
            header.addEventListener('click', () => {
                const bs = bootstrap.Collapse.getOrCreateInstance(target);
                bs.toggle();
            });
        });
    </script>
</body>
</html>
