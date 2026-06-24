<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - {{ config('village.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon/favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon/favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon/favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon/apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('favicon/site.webmanifest') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
</head>
<body>
    <div class="dashboard-shell" data-dashboard-shell>
        <aside class="sidebar" id="dashboardSidebar">
            <a href="{{ route('dashboard.index') }}" class="brand">
                <img src="{{ config('village.logo_url') }}" alt="Logo {{ config('village.district') }}">
                <span class="brand-text">
                    <strong>{{ config('village.name') }}</strong>
                    <small>Panel {{ auth()->user()->role }}</small>
                </span>
            </a>

            <div class="menu-section">
                <p class="menu-title">Utama</p>
                <ul class="menu-list">
                    <li>
                        <a class="{{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}" title="Dashboard">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M3 10.5L12 3l9 7.5V21H3V10.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 21v-6h6v6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            </span>
                            <span class="menu-link-label">Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <p class="menu-title">Manajemen Data</p>
                <ul class="menu-list">
                    <li>
                        <a class="{{ request()->routeIs('dashboard.population-records.*') ? 'active' : '' }}" href="{{ route('dashboard.population-records.index') }}" title="Kependudukan">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M22 20v-1a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Kependudukan</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs(
                            'dashboard.land-transactions.index',
                            'dashboard.land-transactions.create',
                            'dashboard.land-transactions.store',
                            'dashboard.land-transactions.show',
                            'dashboard.land-transactions.edit',
                            'dashboard.land-transactions.update',
                            'dashboard.land-transactions.history',
                            'dashboard.land-transactions.destroy'
                        ) ? 'active' : '' }}" href="{{ route('dashboard.land-transactions.index') }}" title="Pertanahan">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M3 19l6-6 4 4 8-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 9h7v7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span class="menu-link-label">Pertanahan</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.land-transactions.archives') ? 'active' : '' }}" href="{{ route('dashboard.land-transactions.archives') }}" title="Arsip Dokumen Pertanahan">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v4H4zM6 9h12v10H6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Arsip Pertanahan</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.village-activities.*') ? 'active' : '' }}" href="{{ route('dashboard.village-activities.index') }}" title="Kegiatan Desa">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3 11h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Kegiatan Desa</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.news.*') ? 'active' : '' }}" href="{{ route('dashboard.news.index') }}" title="Berita">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h14a2 2 0 0 1 2 2v11H6a2 2 0 0 1-2-2V5Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Berita</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.galleries.*') ? 'active' : '' }}" href="{{ route('dashboard.galleries.index') }}" title="Galeri">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 11h.01M3 16l4.5-4.5a2 2 0 0 1 2.8 0L14 15l2.5-2.5a2 2 0 0 1 2.8 0L21 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span class="menu-link-label">Galeri</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.announcements.*') ? 'active' : '' }}" href="{{ route('dashboard.announcements.index') }}" title="Pengumuman">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 12v-2a2 2 0 0 1 2-2h5l7-3v14l-7-3H6a2 2 0 0 1-2-2v-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 16v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Pengumuman</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.pbb-tax-objects.*') ? 'active' : '' }}" href="{{ route('dashboard.pbb-tax-objects.index') }}" title="Master Data PBB">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 7h8M8 11h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Master Data PBB</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <p class="menu-title">Layanan Desa</p>
                <ul class="menu-list">
                    <li>
                        <a class="{{ request()->routeIs('dashboard.pbb-payment-requests.*') ? 'active' : '' }}" href="{{ route('dashboard.pbb-payment-requests.index') }}" title="Pembayaran PBB">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M7 14h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Pembayaran PBB</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.letter-service-requests.*') ? 'active' : '' }}" href="{{ route('dashboard.letter-service-requests.index') }}" title="Surat Online">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 8l9 6 9-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span class="menu-link-label">Surat Online</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.complaint-reports.*') ? 'active' : '' }}" href="{{ route('dashboard.complaint-reports.index') }}" title="Pengaduan">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M21 12a8.5 8.5 0 0 1-8.5 8.5H6l-3 2v-6.5A8.5 8.5 0 1 1 21 12Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 11h6M9 14h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Pengaduan</span>
                        </a>
                    </li>
                    <li>
                        <a class="{{ request()->routeIs('dashboard.service-archives.*') ? 'active' : '' }}" href="{{ route('dashboard.service-archives.index') }}" title="Arsip Layanan">
                            <span class="menu-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v4H4zM6 9h12v10H6z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M10 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </span>
                            <span class="menu-link-label">Arsip Layanan</span>
                        </a>
                    </li>
                    @if(auth()->user()->isAdmin())
                        <li>
                            <a class="{{ request()->routeIs('dashboard.website-settings.*') ? 'active' : '' }}" href="{{ route('dashboard.website-settings.edit') }}" title="Pengaturan Website">
                                <span class="menu-link-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.08a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.08a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.08a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="1.2"/></svg>
                                </span>
                                <span class="menu-link-label">Pengaturan Website</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>

            @if(auth()->user()->isAdmin())
                <div class="menu-section">
                    <p class="menu-title">Akses Admin</p>
                    <ul class="menu-list">
                        <li>
                            <a class="{{ request()->routeIs('dashboard.operators.*') ? 'active' : '' }}" href="{{ route('dashboard.operators.index') }}" title="Kelola Operator">
                                <span class="menu-link-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l8 4v6c0 5-3.4 8.7-8 9-4.6-.3-8-4-8-9V7l8-4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span class="menu-link-label">Kelola Operator</span>
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
        </aside>

        <button class="sidebar-backdrop" type="button" data-sidebar-backdrop aria-label="Tutup sidebar"></button>

        <div class="content">
            <header class="topbar">
                <div class="topbar-heading">
                    <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-controls="dashboardSidebar" aria-expanded="true" aria-label="Buka/Tutup sidebar">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
                    </button>
                    <div>
                        <h1>@yield('page_title', 'Dashboard')</h1>
                        <small>{{ auth()->user()->name }} ({{ auth()->user()->role }})</small>
                    </div>
                </div>
                <div class="topbar-actions">
                    <a class="btn btn-secondary" href="{{ route('home') }}">Lihat Website</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Keluar</button>
                    </form>
                </div>
            </header>

            <div class="content-body">
                @include('partials.flash')
                @yield('content')
            </div>
        </div>
    </div>
    <script>
        (() => {
            const shell = document.querySelector('[data-dashboard-shell]');
            if (!shell) return;

            const toggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
            const backdrop = shell.querySelector('[data-sidebar-backdrop]');
            const links = shell.querySelectorAll('.menu-list a');
            const mobileMedia = window.matchMedia('(max-width: 1024px)');
            const storageKey = 'webdes.sidebar.collapsed';

            const readCollapsed = () => {
                try {
                    return window.localStorage.getItem(storageKey) === '1';
                } catch (error) {
                    return false;
                }
            };

            const writeCollapsed = (value) => {
                try {
                    window.localStorage.setItem(storageKey, value ? '1' : '0');
                } catch (error) {
                    // Ignore storage write errors (private mode / restricted browser settings).
                }
            };

            const setExpanded = (expanded) => {
                toggles.forEach((toggle) => {
                    toggle.setAttribute('aria-expanded', String(expanded));
                });
            };

            const closeMobile = () => {
                shell.classList.remove('sidebar-open');
                if (mobileMedia.matches) {
                    setExpanded(false);
                }
            };

            const applyLayoutMode = () => {
                if (mobileMedia.matches) {
                    shell.classList.remove('sidebar-collapsed');
                    closeMobile();
                    return;
                }

                shell.classList.remove('sidebar-open');
                const collapsed = readCollapsed();
                shell.classList.toggle('sidebar-collapsed', collapsed);
                setExpanded(!collapsed);
            };

            toggles.forEach((toggle) => {
                toggle.addEventListener('click', () => {
                    if (mobileMedia.matches) {
                        const open = shell.classList.toggle('sidebar-open');
                        setExpanded(open);
                        return;
                    }

                    const collapsed = shell.classList.toggle('sidebar-collapsed');
                    writeCollapsed(collapsed);
                    setExpanded(!collapsed);
                });
            });

            backdrop?.addEventListener('click', closeMobile);

            links.forEach((link) => {
                link.addEventListener('click', () => {
                    if (mobileMedia.matches) {
                        closeMobile();
                    }
                });
            });

            mobileMedia.addEventListener('change', applyLayoutMode);
            applyLayoutMode();
        })();
    </script>
</body>
</html>
