@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Utama')

@section('content')
    <style>
        .monitor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            margin-top: 0.8rem;
        }

        .monitor-card {
            position: relative;
            display: block;
            border-radius: 0.85rem;
            border: 1px solid #cfdbe8;
            padding: 0.8rem;
            background: #f7fbff;
            color: #102b43;
            text-decoration: none;
            box-shadow: 0 3px 10px rgba(15, 76, 129, 0.05);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .monitor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 76, 129, 0.12);
        }

        .monitor-card.is-static:hover {
            transform: none;
            box-shadow: 0 3px 10px rgba(15, 76, 129, 0.05);
        }

        .monitor-card small {
            display: block;
            margin-bottom: 0.3rem;
            color: #244b66;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .monitor-card strong {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 1.3rem;
            line-height: 1.1;
        }

        .monitor-card p {
            margin: 0;
            color: #355973;
            font-size: 0.78rem;
        }

        .monitor-dot {
            position: absolute;
            top: 0.45rem;
            right: 0.45rem;
            min-width: 1.35rem;
            height: 1.35rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #d51f1f;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            border: 2px solid #fff;
            padding: 0 0.28rem;
        }
    </style>

    <section class="panel">
        <div class="toolbar">
            <div>
                <h2>Ringkasan Monitoring</h2>
                <p class="muted" style="margin:0.25rem 0 0;">Menampilkan data periode: <strong data-monitor-period-label>{{ $periodLabel }}</strong>.</p>
            </div>
            <form method="GET" action="{{ route('dashboard.index') }}" class="inline-form dashboard-period-form">
                <div class="field">
                    <label for="period">Filter Periode</label>
                    <select id="period" name="period">
                        @foreach($periodOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPeriod === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Terapkan</button>
            </form>
        </div>

        <div class="monitor-grid" data-monitor-grid>
            @foreach($monitorCards as $card)
                @if($card['route'])
                    <a href="{{ $card['route'] }}" class="monitor-card tone-{{ $card['tone'] }}" data-monitor-key="{{ $card['key'] }}">
                        @if($card['notification'] > 0)
                            <span class="monitor-dot" data-monitor-dot>{{ $card['notification'] }}</span>
                        @endif
                        <small>{{ $card['title'] }}</small>
                        <strong data-monitor-value>{{ number_format($card['value'], 0, ',', '.') }}</strong>
                        <p data-monitor-total>Total data: {{ number_format($card['total'], 0, ',', '.') }}</p>
                    </a>
                @else
                    <article class="monitor-card tone-{{ $card['tone'] }} is-static" data-monitor-key="{{ $card['key'] }}">
                        @if($card['notification'] > 0)
                            <span class="monitor-dot" data-monitor-dot>{{ $card['notification'] }}</span>
                        @endif
                        <small>{{ $card['title'] }}</small>
                        <strong data-monitor-value>{{ number_format($card['value'], 0, ',', '.') }}</strong>
                        <p data-monitor-total>Total data: {{ number_format($card['total'], 0, ',', '.') }}</p>
                    </article>
                @endif
            @endforeach
        </div>
    </section>

    <section class="panel">
        <h2>Grafik Ringkasan</h2>
        <div class="chart-grid-dashboard">
            <article class="chart-box">
                <h3>Kependudukan per Dusun</h3>
                <canvas id="dashboardPopulationChart"></canvas>
            </article>
            <article class="chart-box">
                <h3>Kegiatan per Kategori</h3>
                <canvas id="dashboardActivitiesChart"></canvas>
            </article>
        </div>
        <div class="chart-grid-dashboard" style="margin-top:0.8rem;">
            <article class="chart-box">
                <h3>Anggaran Kegiatan per Tahun (Opsional)</h3>
                <canvas id="dashboardBudgetChart"></canvas>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Shortcut Modul</h2>
        <div class="quick-mini-grid">
            <a class="quick-mini" href="{{ route('dashboard.population-records.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4ZM5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Kependudukan</h3>
                    <p>Kelola data penduduk lengkap per dusun.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.land-transactions.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M3 16l9-8 9 8M5 14v6h14v-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Transaksi Tanah</h3>
                    <p>Pencatatan transaksi Letter C dan arsip dokumen.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.village-activities.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 18h16M7 14v4m5-8v8m5-5v5M5 6h14v2H5z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Kegiatan Desa</h3>
                    <p>Kelola kegiatan desa dan anggaran opsional.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.pbb-payment-requests.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M6 4h12v16H6zM9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Layanan PBB</h3>
                    <p>Verifikasi pengajuan pembayaran PBB.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.letter-service-requests.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM4 8l8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Surat Online</h3>
                    <p>Proses permohonan surat dari warga. Surat baru: {{ $stats['surat_masuk'] }}.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.complaint-reports.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 5h16v11H8l-4 3zM8 9h8M8 12h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Pengaduan</h3>
                    <p>Tindak lanjut pengaduan masyarakat.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.news.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M5 5h14v14H5zM8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Berita</h3>
                    <p>Kelola berita dan artikel desa.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.galleries.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4zM7 14l3-3 2 2 3-3 2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Galeri</h3>
                    <p>Kelola dokumentasi kegiatan desa.</p>
                </div>
            </a>
            <a class="quick-mini" href="{{ route('dashboard.announcements.index') }}">
                <span class="quick-mini-icon">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M4 8h14v8H4zM18 10l2 1v2l-2 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <div>
                    <h3>Pengumuman</h3>
                    <p>Publikasi pengumuman resmi desa.</p>
                </div>
            </a>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const monitorGrid = document.querySelector('[data-monitor-grid]');
        const monitorPeriodLabel = document.querySelector('[data-monitor-period-label]');
        const monitorSummaryEndpoint = @json(route('dashboard.monitoring.summary'));
        const monitorActivePeriod = @json($selectedPeriod);

        const formatMonitorNumber = (value) => new Intl.NumberFormat('id-ID').format(Number(value || 0));

        const applyMonitorCardUpdate = (card) => {
            if (!card || !card.key || !monitorGrid) {
                return;
            }

            const item = monitorGrid.querySelector(`[data-monitor-key="${card.key}"]`);
            if (!item) {
                return;
            }

            const valueEl = item.querySelector('[data-monitor-value]');
            if (valueEl) {
                valueEl.textContent = formatMonitorNumber(card.value);
            }

            const totalEl = item.querySelector('[data-monitor-total]');
            if (totalEl) {
                totalEl.textContent = `Total data: ${formatMonitorNumber(card.total)}`;
            }

            const notificationValue = Number(card.notification || 0);
            let dotEl = item.querySelector('[data-monitor-dot]');

            if (notificationValue > 0) {
                if (!dotEl) {
                    dotEl = document.createElement('span');
                    dotEl.className = 'monitor-dot';
                    dotEl.setAttribute('data-monitor-dot', '1');
                    item.appendChild(dotEl);
                }
                dotEl.textContent = formatMonitorNumber(notificationValue);
            } else if (dotEl) {
                dotEl.remove();
            }
        };

        const refreshMonitoringSummary = async () => {
            if (!monitorGrid || !monitorSummaryEndpoint) {
                return;
            }

            const requestUrl = `${monitorSummaryEndpoint}?period=${encodeURIComponent(monitorActivePeriod)}`;

            try {
                const response = await fetch(requestUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                if (payload?.period?.label && monitorPeriodLabel) {
                    monitorPeriodLabel.textContent = payload.period.label;
                }

                if (Array.isArray(payload?.cards)) {
                    payload.cards.forEach(applyMonitorCardUpdate);
                }
            } catch (_) {
                // Silent fallback: dashboard tetap bisa dipakai walau refresh gagal.
            }
        };

        if (monitorGrid) {
            refreshMonitoringSummary();
            window.setInterval(refreshMonitoringSummary, 30000);
        }

        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];

        const populationLabels = @json($populationChart['labels']);
        const populationValues = @json($populationChart['data']);
        const populationColors = populationLabels.map((_, i) => palette[i % palette.length]);

        const activitiesLabels = @json($activitiesChart['labels']);
        const activitiesValues = @json($activitiesChart['data']);
        const activitiesColors = activitiesLabels.map((_, i) => palette[i % palette.length]);

        const budgetLabels = @json($budgetChart['labels']);
        const budgetValues = @json($budgetChart['data']);
        const budgetColors = budgetLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('dashboardPopulationChart'), {
            type: 'bar',
            data: {
                labels: populationLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: populationValues,
                    backgroundColor: populationColors,
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('dashboardActivitiesChart'), {
            type: 'bar',
            data: {
                labels: activitiesLabels,
                datasets: [{
                    label: 'Jumlah Kegiatan',
                    data: activitiesValues,
                    backgroundColor: activitiesColors,
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('dashboardBudgetChart'), {
            type: 'bar',
            data: {
                labels: budgetLabels,
                datasets: [{
                    label: 'Total Anggaran',
                    data: budgetValues,
                    backgroundColor: budgetColors,
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
@endsection
