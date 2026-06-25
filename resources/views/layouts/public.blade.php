<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('village.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Portal Informasi dan Layanan Online Resmi Pemerintah Desa ' . config('village.name') . ', ' . config('village.district') . '. Akses mandiri untuk surat online, PBB, pengaduan warga, dan berita desa terbaru.')">
    <meta name="keywords" content="@yield('meta_keywords', 'desa, ' . config('village.name') . ', ' . config('village.district') . ', portal desa, layanan online, surat online, pbb desa, pengaduan warga, berita desa')">
    <meta name="author" content="{{ config('village.developed_by', 'Pemerintah Desa ' . config('village.name')) }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:title" content="@yield('title', config('village.name'))">
    <meta property="og:description" content="@yield('meta_description', 'Portal Informasi dan Layanan Online Resmi Pemerintah Desa ' . config('village.name') . ', ' . config('village.district') . '.')">
    <meta property="og:image" content="@yield('og_image', asset('assets/images/loog_pekalongan.png'))">
    <meta property="og:site_name" content="SID {{ config('village.name') }}">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ request()->url() }}">
    <meta property="twitter:title" content="@yield('title', config('village.name'))">
    <meta property="twitter:description" content="@yield('meta_description', 'Portal Informasi dan Layanan Online Resmi Pemerintah Desa ' . config('village.name') . ', ' . config('village.district') . '.')">
    <meta property="twitter:image" content="@yield('og_image', asset('assets/images/loog_pekalongan.png'))">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ request()->url() }}">

    <!-- JSON-LD Structured Data for GovernmentOrganization & WebSite -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "GovernmentOrganization",
      "name": "Pemerintah Desa {{ config('village.name') }}",
      "alternateName": "Pemdes {{ config('village.name') }}",
      "url": "{{ url('/') }}",
      "logo": "{{ asset('assets/images/loog_pekalongan.png') }}",
      "address": {
        "@@type": "PostalAddress",
        "streetAddress": "{{ config('village.address') }}",
        "addressLocality": "{{ config('village.district') }}",
        "addressCountry": "ID"
      },
      "contactPoint": {
        "@@type": "ContactPoint",
        "telephone": "{{ config('village.phone') }}",
        "contactType": "customer service",
        "email": "{{ config('village.email') }}"
      },
      "sameAs": [
        "{{ config('village.instagram_url') }}",
        "{{ config('village.facebook_url') }}"
      ]
    }
    </script>
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebSite",
      "name": "SID {{ config('village.name') }}",
      "url": "{{ url('/') }}",
      "potentialAction": {
        "@@type": "SearchAction",
        "target": "{{ url('/berita') }}?q={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>

    <link rel="icon" type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}" />
    <script>
        (function () {
            const storageKey = 'sid_theme';
            let storedTheme = null;
            try {
                storedTheme = window.localStorage ? localStorage.getItem(storageKey) : null;
            } catch (error) {
                storedTheme = null;
            }
            const systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                ? 'dark'
                : 'light';
            const initialTheme = storedTheme === 'dark' || storedTheme === 'light'
                ? storedTheme
                : systemTheme;

            document.documentElement.setAttribute('data-theme', initialTheme);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('css/public-service-form.css') }}">
</head>
<body>
    @php
        $phone = (string) config('village.phone');
        $phoneDial = preg_replace('/[^0-9+]/', '', $phone);
        $mapLink = config('village.map_link_url', str_replace('&output=embed', '', (string) config('village.map_embed_url')));
        $showWelcomeBanner = request()->routeIs('home') && filled(config('village.welcome_banner_url'));
        $welcomeBannerAlt = (string) config('village.welcome_banner_alt', 'Banner selamat datang ' . config('village.name'));
        $welcomeBannerCooldownHours = max(1, (int) config('village.welcome_banner_cooldown_hours', 24));
    @endphp

    <div class="site-shell" data-site-shell>
    <div class="page-progress" data-page-progress aria-hidden="true"></div>
    <header class="site-header">
        <div class="container site-header-inner">
            <a href="{{ route('home') }}" class="brand" data-admin-entry="{{ route('login') }}">
                <img src="{{ config('village.logo_url') }}" alt="Logo {{ config('village.district') }}">
                <span>
                    {{ config('village.name') }}
                    <small>{{ config('village.district') }}</small>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="main-nav" aria-label="Buka atau tutup menu navigasi">
                <span class="nav-toggle-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <span class="nav-toggle-text">Menu</span>
            </button>

            <nav class="main-nav" data-main-nav id="main-nav">
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

                    <li class="has-dropdown" data-dropdown>
                        <button type="button">Lembaga</button>
                        <div class="dropdown">
                            <a href="http://bpd.desalambanggelun.web.id" target="_blank" rel="noopener">BPD</a>
                            <a href="http://bumdes.desalambanggelun.web.id" target="_blank" rel="noopener">BUMDes</a>
                            <a href="http://karangtaruna.desalambanggelun.web.id" target="_blank" rel="noopener">Karangtaruna</a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="header-actions">
                <button
                    type="button"
                    class="theme-toggle"
                    data-theme-toggle
                    aria-label="Ubah mode tampilan"
                    aria-pressed="false"
                    title="Ubah mode terang/gelap"
                >
                    <span class="theme-toggle-icon sun" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="4.5" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.5V5.2M12 18.8V21.5M21.5 12h-2.7M5.2 12H2.5M18.7 5.3l-1.9 1.9M7.2 16.8l-1.9 1.9M18.7 18.7l-1.9-1.9M7.2 7.2 5.3 5.3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </span>
                    <span class="theme-toggle-icon moon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M20.5 14.2A8.5 8.5 0 1 1 9.8 3.5a7 7 0 1 0 10.7 10.7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    </span>
                </button>
                @auth
                    <a class="btn btn-outline" href="{{ route('dashboard.index') }}">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Keluar</button>
                    </form>
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
    </div>

    @if($showWelcomeBanner)
        <div
            class="welcome-banner-overlay"
            data-welcome-banner-overlay
            data-cooldown-hours="{{ $welcomeBannerCooldownHours }}"
            hidden
            aria-hidden="true"
        >
            <div
                class="welcome-banner-dialog"
                data-welcome-banner-dialog
                role="dialog"
                aria-modal="true"
                aria-label="Banner selamat datang"
                tabindex="-1"
            >
                <div class="welcome-banner-card">
                    <img
                        class="welcome-banner-image"
                        src="{{ config('village.welcome_banner_url') }}"
                        alt="{{ $welcomeBannerAlt }}"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
            </div>
        </div>
    @endif

    <script>
        const navToggle = document.querySelector('[data-nav-toggle]');
        const mainNav = document.querySelector('[data-main-nav]');
        const dropdowns = document.querySelectorAll('[data-dropdown] > button');
        const brandEntry = document.querySelector('[data-admin-entry]');
        const pageProgress = document.querySelector('[data-page-progress]');
        const themeToggle = document.querySelector('[data-theme-toggle]');
        const rootElement = document.documentElement;
        const siteShell = document.querySelector('[data-site-shell]');
        const welcomeBannerOverlay = document.querySelector('[data-welcome-banner-overlay]');
        const welcomeBannerDialog = document.querySelector('[data-welcome-banner-dialog]');
        const welcomeBannerImage = document.querySelector('.welcome-banner-image');

        const themeStorageKey = 'sid_theme';
        const getSystemTheme = () => (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
            ? 'dark'
            : 'light';
        const readStoredTheme = () => {
            try {
                const stored = localStorage.getItem(themeStorageKey);
                return stored === 'dark' || stored === 'light' ? stored : null;
            } catch (error) {
                return null;
            }
        };

        const applyTheme = (theme, { persist = true, emit = true } = {}) => {
            const normalized = theme === 'dark' ? 'dark' : 'light';
            rootElement.setAttribute('data-theme', normalized);

            if (persist) {
                try {
                    localStorage.setItem(themeStorageKey, normalized);
                } catch (error) {
                    // Ignore storage failure
                }
            }

            if (themeToggle) {
                const isDark = normalized === 'dark';
                themeToggle.classList.toggle('is-dark', isDark);
                themeToggle.setAttribute('aria-pressed', String(isDark));
                themeToggle.setAttribute(
                    'aria-label',
                    isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'
                );
                themeToggle.setAttribute(
                    'title',
                    isDark ? 'Pindah ke mode terang' : 'Pindah ke mode gelap'
                );
            }

            if (emit) {
                window.dispatchEvent(new CustomEvent('app:theme-change', {
                    detail: { theme: normalized },
                }));
            }
        };

        const bootTheme = () => {
            const initialTheme = readStoredTheme() || rootElement.getAttribute('data-theme') || getSystemTheme();
            applyTheme(initialTheme, { persist: false, emit: false });

            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const current = rootElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                    applyTheme(current === 'dark' ? 'light' : 'dark');
                });
            }

            if (window.matchMedia) {
                const systemThemeMedia = window.matchMedia('(prefers-color-scheme: dark)');
                const syncSystemTheme = () => {
                    if (readStoredTheme()) {
                        return;
                    }
                    applyTheme(systemThemeMedia.matches ? 'dark' : 'light', { persist: false });
                };

                if (typeof systemThemeMedia.addEventListener === 'function') {
                    systemThemeMedia.addEventListener('change', syncSystemTheme);
                } else if (typeof systemThemeMedia.addListener === 'function') {
                    systemThemeMedia.addListener(syncSystemTheme);
                }
            }
        };

        bootTheme();

        const bannerStorage = (() => {
            const candidates = [];

            try {
                if (window.localStorage) {
                    candidates.push(window.localStorage);
                }
            } catch (error) {
                // Ignore storage access failure
            }

            try {
                if (window.sessionStorage) {
                    candidates.push(window.sessionStorage);
                }
            } catch (error) {
                // Ignore storage access failure
            }

            return {
                getItem(key) {
                    for (const storage of candidates) {
                        try {
                            const value = storage.getItem(key);
                            if (value !== null) {
                                return value;
                            }
                        } catch (error) {
                            // Ignore storage access failure
                        }
                    }

                    return null;
                },
                setItem(key, value) {
                    for (const storage of candidates) {
                        try {
                            storage.setItem(key, value);
                            return true;
                        } catch (error) {
                            // Try the next storage option
                        }
                    }

                    return false;
                },
            };
        })();

        const welcomeBannerStorageKey = 'sid_welcome_banner_closed_at';
        const welcomeBannerCooldownMs = Math.max(
            1,
            Number(welcomeBannerOverlay?.getAttribute('data-cooldown-hours') || '24')
        ) * 60 * 60 * 1000;
        const welcomeBannerAutoCloseDelayMs = 5000;
        let welcomeBannerOpenTimer = null;
        let welcomeBannerCloseTimer = null;
        let welcomeBannerAutoCloseTimer = null;
        let welcomeBannerRevealTimer = null;

        const setWelcomeBannerState = (isOpen) => {
            document.body.classList.toggle('welcome-banner-open', isOpen);
            siteShell?.classList.toggle('is-welcome-banner-blurred', isOpen);

            if (siteShell && 'inert' in siteShell) {
                siteShell.inert = isOpen;
            }
        };

        const readWelcomeBannerClosedAt = () => {
            const rawValue = bannerStorage.getItem(welcomeBannerStorageKey);
            const parsedValue = Number(rawValue);
            return Number.isFinite(parsedValue) ? parsedValue : 0;
        };

        const shouldShowWelcomeBanner = () => {
            if (!welcomeBannerOverlay || !welcomeBannerDialog) {
                return false;
            }

            const closedAt = readWelcomeBannerClosedAt();
            if (closedAt <= 0) {
                return true;
            }

            return Date.now() - closedAt >= welcomeBannerCooldownMs;
        };

        const scheduleWelcomeBannerAutoClose = () => {
            window.clearTimeout(welcomeBannerAutoCloseTimer);
            welcomeBannerAutoCloseTimer = window.setTimeout(() => {
                closeWelcomeBanner();
            }, welcomeBannerAutoCloseDelayMs);
        };

        const startWelcomeBannerAutoClose = () => {
            if (!welcomeBannerOverlay || welcomeBannerOverlay.hidden) {
                return;
            }

            if (!welcomeBannerImage) {
                scheduleWelcomeBannerAutoClose();
                return;
            }

            if (welcomeBannerImage.complete) {
                scheduleWelcomeBannerAutoClose();
                return;
            }

            const handleReady = () => {
                welcomeBannerImage.removeEventListener('load', handleReady);
                welcomeBannerImage.removeEventListener('error', handleReady);
                scheduleWelcomeBannerAutoClose();
            };

            welcomeBannerImage.addEventListener('load', handleReady, { once: true });
            welcomeBannerImage.addEventListener('error', handleReady, { once: true });
        };

        const openWelcomeBanner = () => {
            if (!welcomeBannerOverlay || !welcomeBannerDialog || !siteShell) {
                return;
            }

            welcomeBannerOpenTimer = null;
            window.clearTimeout(welcomeBannerCloseTimer);
            welcomeBannerOverlay.hidden = false;
            welcomeBannerOverlay.setAttribute('aria-hidden', 'false');
            setWelcomeBannerState(true);

            window.requestAnimationFrame(() => {
                welcomeBannerOverlay.classList.remove('is-leaving');
                welcomeBannerOverlay.classList.add('is-visible');
                welcomeBannerDialog.focus({ preventScroll: true });
                startWelcomeBannerAutoClose();
            });
        };

        const closeWelcomeBanner = () => {
            if (!welcomeBannerOverlay || !welcomeBannerDialog || welcomeBannerOverlay.hidden) {
                return;
            }

            window.clearTimeout(welcomeBannerOpenTimer);
            window.clearTimeout(welcomeBannerAutoCloseTimer);
            bannerStorage.setItem(welcomeBannerStorageKey, String(Date.now()));
            welcomeBannerOverlay.classList.remove('is-visible');
            welcomeBannerOverlay.classList.add('is-leaving');
            welcomeBannerOverlay.setAttribute('aria-hidden', 'true');
            setWelcomeBannerState(false);

            if (siteShell) {
                siteShell.classList.remove('is-welcome-banner-revealing');
                void siteShell.offsetWidth;
                siteShell.classList.add('is-welcome-banner-revealing');
                window.clearTimeout(welcomeBannerRevealTimer);
                welcomeBannerRevealTimer = window.setTimeout(() => {
                    siteShell.classList.remove('is-welcome-banner-revealing');
                }, 820);
            }

            window.clearTimeout(welcomeBannerCloseTimer);
            welcomeBannerCloseTimer = window.setTimeout(() => {
                welcomeBannerOverlay.hidden = true;
                welcomeBannerOverlay.classList.remove('is-leaving');
            }, 780);
        };

        if (shouldShowWelcomeBanner()) {
            welcomeBannerOpenTimer = window.setTimeout(openWelcomeBanner, 700);
        }

        welcomeBannerOverlay?.addEventListener('click', () => {
            closeWelcomeBanner();
        });

        window.addEventListener('keydown', (event) => {
            if (!welcomeBannerOverlay || welcomeBannerOverlay.hidden) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeWelcomeBanner();
            }
        });

        let progressFrame = null;
        let progressValue = 0;
        const startPageProgress = () => {
            if (!pageProgress || pageProgress.classList.contains('is-active')) {
                return;
            }

            progressValue = 0.15;
            pageProgress.classList.remove('is-complete');
            pageProgress.classList.add('is-active');
            pageProgress.style.transform = `scaleX(${progressValue})`;

            const advance = () => {
                progressValue = Math.min(progressValue + (1 - progressValue) * 0.12, 0.92);
                pageProgress.style.transform = `scaleX(${progressValue})`;
                progressFrame = window.setTimeout(advance, 120);
            };

            progressFrame = window.setTimeout(advance, 120);
        };

        const stopPageProgress = () => {
            if (!pageProgress) {
                return;
            }

            if (progressFrame) {
                clearTimeout(progressFrame);
                progressFrame = null;
            }

            pageProgress.style.transform = 'scaleX(1)';
            pageProgress.classList.add('is-complete');

            window.setTimeout(() => {
                pageProgress.classList.remove('is-active', 'is-complete');
                pageProgress.style.transform = 'scaleX(0)';
            }, 260);
        };

        const isInternalNavigation = (anchor) => {
            if (!(anchor instanceof HTMLAnchorElement)) {
                return false;
            }

            if (anchor.target === '_blank' || anchor.hasAttribute('download')) {
                return false;
            }

            const href = anchor.getAttribute('href') || '';
            if (href.startsWith('#') || href.startsWith('javascript:')) {
                return false;
            }

            try {
                const url = new URL(anchor.href, window.location.origin);
                return url.origin === window.location.origin;
            } catch (error) {
                return false;
            }
        };

        window.addEventListener('pageshow', stopPageProgress);
        window.addEventListener('load', stopPageProgress);
        window.addEventListener('beforeunload', startPageProgress);

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const anchor = target.closest('a');
            if (isInternalNavigation(anchor)) {
                startPageProgress();
            }
        }, true);

        if (navToggle && mainNav) {
            navToggle.addEventListener('click', () => {
                const isOpen = mainNav.classList.toggle('show');
                navToggle.classList.toggle('is-active', isOpen);
                navToggle.setAttribute('aria-expanded', String(isOpen));

                if (!isOpen) {
                    dropdowns.forEach((button) => {
                        button.parentElement?.classList.remove('open');
                        button.setAttribute('aria-expanded', 'false');
                    });
                }
            });
        }

        dropdowns.forEach((button) => {
            button.setAttribute('aria-expanded', 'false');

            button.addEventListener('click', () => {
                if (window.innerWidth <= 920) {
                    const parent = button.parentElement;
                    if (!parent) {
                        return;
                    }

                    const willOpen = !parent.classList.contains('open');
                    dropdowns.forEach((otherButton) => {
                        if (otherButton === button) {
                            return;
                        }

                        otherButton.parentElement?.classList.remove('open');
                        otherButton.setAttribute('aria-expanded', 'false');
                    });

                    parent.classList.toggle('open', willOpen);
                    button.setAttribute('aria-expanded', String(willOpen));
                }
            });
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 920 && mainNav && navToggle) {
                mainNav.classList.remove('show');
                navToggle.classList.remove('is-active');
                navToggle.setAttribute('aria-expanded', 'false');
                dropdowns.forEach((button) => {
                    button.parentElement?.classList.remove('open');
                    button.setAttribute('aria-expanded', 'false');
                });
            }
        });

        const triggerSecretLogin = () => {
            if (!brandEntry) {
                return;
            }

            const secretUrl = brandEntry.getAttribute('data-admin-entry');
            if (!secretUrl) {
                return;
            }

            brandEntry.classList.add('brand-secret-active');
            startPageProgress();
            window.setTimeout(() => {
                window.location.assign(secretUrl);
            }, 180);
        };

        if (brandEntry) {
            let lastTapAt = 0;

            brandEntry.addEventListener('dblclick', (event) => {
                event.preventDefault();
                triggerSecretLogin();
            });

            brandEntry.addEventListener('touchend', (event) => {
                const now = Date.now();
                if (now - lastTapAt < 420) {
                    event.preventDefault();
                    lastTapAt = 0;
                    triggerSecretLogin();
                    return;
                }

                lastTapAt = now;
            }, { passive: false });
        }

        const buttonStateStore = new WeakMap();
        const setButtonBusy = (button, loadingText = 'Memproses...') => {
            if (!(button instanceof HTMLElement) || button.classList.contains('is-loading')) {
                return;
            }

            if (button instanceof HTMLButtonElement || button instanceof HTMLInputElement) {
                button.disabled = true;
            }

            buttonStateStore.set(
                button,
                button instanceof HTMLInputElement ? button.value : button.innerHTML
            );
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');

            if (button instanceof HTMLInputElement) {
                button.value = loadingText;
                return;
            }

            button.innerHTML = `<span class="btn-spinner" aria-hidden="true"></span><span>${loadingText}</span>`;
        };

        const releaseButtonBusy = (button) => {
            if (!(button instanceof HTMLElement) || !button.classList.contains('is-loading')) {
                return;
            }

            const originalContent = buttonStateStore.get(button);
            if (typeof originalContent === 'string') {
                if (button instanceof HTMLInputElement) {
                    button.value = originalContent;
                } else {
                    button.innerHTML = originalContent;
                }
            }

            if (button instanceof HTMLButtonElement || button instanceof HTMLInputElement) {
                button.disabled = false;
            }

            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            buttonStateStore.delete(button);
        };

        window.AppUi = {
            setButtonBusy,
            releaseButtonBusy,
            getActiveTheme: () => rootElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light',
        };

        document.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (String(form.method || 'get').toLowerCase() === 'get') {
                return;
            }

            const submitter = event.submitter instanceof HTMLElement
                ? event.submitter
                : form.querySelector('button[type="submit"], input[type="submit"]');
            if (!(submitter instanceof HTMLElement)) {
                return;
            }

            setButtonBusy(submitter, submitter.getAttribute('data-loading-text') || 'Memproses...');
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

        const sharePanels = Array.from(document.querySelectorAll('[data-share-panel]'));

        const closeSharePanel = (panel) => {
            if (!(panel instanceof HTMLElement)) {
                return;
            }

            const toggle = panel.querySelector('[data-share-toggle]');
            const popover = panel.querySelector('[data-share-popover]');
            if (!(toggle instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
                return;
            }

            panel.classList.remove('is-open');
            popover.hidden = true;
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        };

        const openSharePanel = (panel) => {
            if (!(panel instanceof HTMLElement)) {
                return;
            }

            sharePanels.forEach((item) => {
                if (item !== panel) {
                    closeSharePanel(item);
                }
            });

            const toggle = panel.querySelector('[data-share-toggle]');
            const popover = panel.querySelector('[data-share-popover]');
            if (!(toggle instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
                return;
            }

            popover.hidden = false;
            panel.classList.add('is-open');
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        };

        sharePanels.forEach((panel) => {
            const toggle = panel.querySelector('[data-share-toggle]');
            const actions = panel.querySelectorAll('[data-share-action]');

            if (toggle instanceof HTMLElement) {
                toggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (panel.classList.contains('is-open')) {
                        closeSharePanel(panel);
                        return;
                    }

                    openSharePanel(panel);
                });
            }

            actions.forEach((action) => {
                action.addEventListener('click', () => {
                    closeSharePanel(panel);
                });
            });
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            sharePanels.forEach((panel) => {
                if (!panel.contains(target)) {
                    closeSharePanel(panel);
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                sharePanels.forEach((panel) => closeSharePanel(panel));
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
