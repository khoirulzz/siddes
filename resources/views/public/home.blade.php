@extends('layouts.public')

@section('title', 'Beranda - ' . config('village.name'))

@section('content')
    <section class="hero hero-with-image hero-centered" style="--hero-image: url('{{ config('village.hero_image_url') }}');">
        <h1>Selamat Datang di Website Desa Lambanggelun</h1>
        <p>
            Portal resmi pemerintahan desa untuk informasi publik, layanan digital desa,
            serta publikasi yang transparan.
        </p>
        <div class="hero-meta">
            <span>{{ config('village.district') }}</span>
            <span>Transparan</span>
            <span>Desa Digital</span>
            <span>Pelayanan Publik</span>
        </div>
    </section>

    <section>
        <div class="section-title">
            <h2>Dashboard Publik</h2>
            <span class="muted">Ringkasan visual data desa</span>
        </div>
        <div class="home-insight-grid">
            <article class="insight-card interactive-card">
                <h3>Grafik Penduduk per Dusun</h3>
                <canvas id="homePopulationChart"></canvas>
            </article>
            <article class="insight-card interactive-card">
                <h3>Grafik Data Pertanahan</h3>
                <canvas id="homeLandChart"></canvas>
            </article>
            <article class="insight-card interactive-card">
                <h3>Grafik Kegiatan Desa</h3>
                <canvas id="homeActivitiesChart"></canvas>
            </article>
        </div>
    </section>

    <section>
        <div class="section-title">
            <h2>Layanan Desa</h2>
            <span class="muted">Klik untuk masuk ke layanan</span>
        </div>
        <div class="service-action-grid">
            <a class="service-action service-tone-ocean" href="{{ route('services.letter') }}">
                <span class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM4 8l8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <span>Surat Online</span>
            </a>
            <a class="service-action service-tone-teal" href="{{ route('services.pbb') }}">
                <span class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M6 4h12v16H6zM9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <span>PBB</span>
            </a>
            <a class="service-action service-tone-amber" href="{{ route('services.complaint') }}">
                <span class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v11H8l-4 3zM8 9h8M8 12h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <span>Pengaduan</span>
            </a>
        </div>
    </section>

    <section class="news-highlight">
        <div class="section-title">
            <h2>Berita Terbaru</h2>
            <a class="muted" href="{{ route('news.index') }}">Semua berita</a>
        </div>

        <div class="news-carousel" data-news-carousel>
            <div class="news-track">
                @forelse($news as $item)
                    <article class="news-slide interactive-card">
                        @if($item->thumbnail_url)
                            <img class="news-slide-thumb" src="{{ $item->thumbnail_url }}" alt="{{ $item->title }}">
                        @endif
                        <p class="news-slide-date">{{ $item->published_at?->translatedFormat('d M Y') }}</p>
                        <h3><a href="{{ route('news.show', $item) }}">{{ $item->title }}</a></h3>
                        <p>{{ \Illuminate\Support\Str::limit($item->excerpt, 170) }}</p>
                        <a href="{{ route('news.show', $item) }}">Baca selengkapnya</a>
                    </article>
                @empty
                    <article class="news-slide">
                        <p>Belum ada berita.</p>
                    </article>
                @endforelse
            </div>
            @if($news->count() > 1)
                <button class="news-nav news-nav-prev" type="button" data-news-prev aria-label="Berita sebelumnya">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button class="news-nav news-nav-next" type="button" data-news-next aria-label="Berita berikutnya">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="news-dots" data-news-dots></div>
            @endif
        </div>
    </section>

    <section>
        <div class="section-title">
            <h2>Pengumuman</h2>
            <a class="muted" href="{{ route('announcements.index') }}">Semua pengumuman</a>
        </div>

        <div class="announcement-home-grid">
            @forelse ($announcements as $item)
                <article class="list-item announcement-list-item interactive-card">
                    <img class="announcement-thumb" src="{{ $item->thumbnail_url }}" alt="Ikon pengumuman">
                    <div>
                        <h3><a href="{{ route('announcements.show', $item) }}">{{ $item->title }}</a></h3>
                        <p>{{ \Illuminate\Support\Str::limit($item->content, 170) }}</p>
                        <div class="announcement-actions">
                            <a class="announcement-readmore" href="{{ route('announcements.show', $item) }}">Baca selengkapnya</a>
                            @if($item->link_url)
                                <a class="announcement-readmore external" href="{{ $item->link_url }}" target="_blank" rel="noopener">Buka link terkait</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="list-item"><p>Belum ada pengumuman aktif.</p></div>
            @endforelse
        </div>
    </section>

    <section>
        <div class="section-title">
            <h2>Galeri Kegiatan</h2>
            <a class="muted" href="{{ route('gallery.index') }}">Semua galeri</a>
        </div>

        <div class="news-carousel gallery-carousel" data-gallery-carousel>
            <div class="news-track">
            @forelse ($galleries as $item)
                <article class="news-slide gallery-slide interactive-card">
                    <a class="gallery-slide-link" href="{{ $item->image_url }}" target="_blank" rel="noopener">
                        <img class="news-slide-thumb gallery-slide-thumb" src="{{ $item->image_url }}" alt="{{ $item->title }}">
                        <p class="news-slide-date">{{ ($item->activity_date ?? $item->created_at)?->translatedFormat('d M Y') }}</p>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($item->description, 120) }}</p>
                        <span class="gallery-slide-cta">Lihat foto</span>
                    </a>
                </article>
            @empty
                <article class="news-slide">
                    <p>Belum ada data galeri.</p>
                </article>
            @endforelse
            </div>
            @if($galleries->count() > 1)
                <button class="news-nav news-nav-prev" type="button" data-news-prev aria-label="Galeri sebelumnya">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button class="news-nav news-nav-next" type="button" data-news-next aria-label="Galeri berikutnya">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="news-dots" data-news-dots></div>
            @endif
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];

        const populationLabels = @json($populationChart['labels']);
        const populationData = @json($populationChart['data']);
        const populationColors = populationLabels.map((_, i) => palette[i % palette.length]);

        const landLabels = @json($landChart['labels']);
        const landData = @json($landChart['data']);
        const landColors = landLabels.map((_, i) => palette[i % palette.length]);

        const activitiesLabels = @json($activitiesChart['labels']);
        const activitiesData = @json($activitiesChart['data']);
        const activitiesColors = activitiesLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('homePopulationChart'), {
            type: 'bar',
            data: {
                labels: populationLabels,
                datasets: [{
                    label: 'Penduduk',
                    data: populationData,
                    backgroundColor: populationColors,
                    borderRadius: 7
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('homeLandChart'), {
            type: 'bar',
            data: {
                labels: landLabels,
                datasets: [{
                    label: 'Pertanahan',
                    data: landData,
                    backgroundColor: landColors,
                    borderRadius: 7
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('homeActivitiesChart'), {
            type: 'bar',
            data: {
                labels: activitiesLabels,
                datasets: [{
                    label: 'Kegiatan',
                    data: activitiesData,
                    backgroundColor: activitiesColors,
                    borderRadius: 7
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        const initCarousel = (carousel, options = {}) => {
            if (!carousel) {
                return;
            }

            const track = carousel.querySelector('.news-track');
            const slides = Array.from(carousel.querySelectorAll('.news-slide'));
            if (!track || slides.length === 0) {
                return;
            }

            const dotsWrap = carousel.querySelector('[data-news-dots]');
            const prevBtn = carousel.querySelector('[data-news-prev]');
            const nextBtn = carousel.querySelector('[data-news-next]');
            const cssVisibleVar = options.cssVisibleVar || '--news-visible';
            const autoplayMs = options.autoplayMs || 4500;
            let activeIndex = 0;
            let positions = 1;
            let visibleCount = 3;
            let intervalId = null;
            let touchStartX = 0;
            let touchCurrentX = 0;
            let isTouching = false;
            const swipeThreshold = 45;

            const getVisibleCount = () => {
                if (window.innerWidth < 768) return 1;
                if (window.innerWidth < 1100) return 2;
                return 3;
            };

            const getGap = () => {
                const styles = window.getComputedStyle(track);
                const gap = styles.gap || styles.columnGap || '0';
                return parseFloat(gap) || 0;
            };

            const goTo = (targetIndex, { resetTimer = true } = {}) => {
                if (positions < 2) {
                    activeIndex = 0;
                } else {
                    activeIndex = (targetIndex + positions) % positions;
                }

                renderSlide();
                if (resetTimer) {
                    startAutoSlide();
                }
            };

            const buildDots = () => {
                if (!dotsWrap) return [];
                dotsWrap.innerHTML = '';
                if (positions < 2) return [];
                return Array.from({ length: positions }, (_, idx) => {
                    const dot = document.createElement('button');
                    dot.type = 'button';
                    dot.className = idx === 0 ? 'active' : '';
                    dot.addEventListener('click', () => goTo(idx));
                    dotsWrap.appendChild(dot);
                    return dot;
                });
            };

            let dots = [];

            const renderSlide = () => {
                const slideWidth = slides[0]?.getBoundingClientRect().width || 0;
                const offset = activeIndex * (slideWidth + getGap());
                track.style.transform = `translateX(-${offset}px)`;
                dots.forEach((dot, idx) => dot.classList.toggle('active', idx === activeIndex));
            };

            const stopAutoSlide = () => {
                if (intervalId) {
                    clearInterval(intervalId);
                    intervalId = null;
                }
            };

            const startAutoSlide = () => {
                stopAutoSlide();
                if (positions < 2) return;
                intervalId = setInterval(() => {
                    goTo(activeIndex + 1, { resetTimer: false });
                }, autoplayMs);
            };

            const recalculate = () => {
                visibleCount = getVisibleCount();
                carousel.style.setProperty(cssVisibleVar, String(visibleCount));
                positions = Math.max(1, slides.length - visibleCount + 1);
                if (activeIndex >= positions) {
                    activeIndex = 0;
                }

                dots = buildDots();
                renderSlide();
                startAutoSlide();
            };

            carousel.addEventListener('mouseenter', stopAutoSlide);
            carousel.addEventListener('mouseleave', startAutoSlide);
            carousel.addEventListener('focusin', stopAutoSlide);
            carousel.addEventListener('focusout', startAutoSlide);

            prevBtn?.addEventListener('click', () => goTo(activeIndex - 1));
            nextBtn?.addEventListener('click', () => goTo(activeIndex + 1));

            carousel.addEventListener('touchstart', (event) => {
                if (positions < 2) return;
                isTouching = true;
                touchStartX = event.touches[0].clientX;
                touchCurrentX = touchStartX;
                stopAutoSlide();
            }, { passive: true });

            carousel.addEventListener('touchmove', (event) => {
                if (!isTouching) return;
                touchCurrentX = event.touches[0].clientX;
            }, { passive: true });

            carousel.addEventListener('touchend', () => {
                if (!isTouching) return;
                const distance = touchCurrentX - touchStartX;
                if (Math.abs(distance) >= swipeThreshold) {
                    goTo(distance < 0 ? activeIndex + 1 : activeIndex - 1, { resetTimer: false });
                }
                isTouching = false;
                startAutoSlide();
            });

            carousel.addEventListener('touchcancel', () => {
                isTouching = false;
                startAutoSlide();
            });

            window.addEventListener('resize', recalculate);
            recalculate();
        };

        initCarousel(document.querySelector('[data-news-carousel]'), { cssVisibleVar: '--news-visible' });
        initCarousel(document.querySelector('[data-gallery-carousel]'), { cssVisibleVar: '--gallery-visible' });
    </script>
@endsection
