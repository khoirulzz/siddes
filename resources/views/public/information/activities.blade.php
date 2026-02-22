@extends('layouts.public')

@section('title', 'Informasi Publik - Kegiatan Desa')

@section('content')
    <div class="section-title">
        <h2>Informasi Publik: Kegiatan Desa</h2>
        <span class="muted">Publikasi kegiatan beserta informasi anggaran opsional</span>
    </div>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Total Kegiatan</small>
            <strong>{{ number_format($summary['total'], 0, ',', '.') }} kegiatan</strong>
        </article>
        <article class="summary-card">
            <small>Total Anggaran Tercatat</small>
            <strong>Rp {{ number_format($summary['total_budget'], 0, ',', '.') }}</strong>
        </article>
        <article class="summary-card">
            <small>Kegiatan dengan Anggaran</small>
            <strong>{{ number_format($summary['with_budget'], 0, ',', '.') }}</strong>
        </article>
        <article class="summary-card">
            <small>Kegiatan Tanpa Anggaran</small>
            <strong>{{ number_format($summary['without_budget'], 0, ',', '.') }}</strong>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h3>Kegiatan per Kategori</h3>
            <canvas id="activityCategoryChart"></canvas>
        </article>
        <article class="chart-card">
            <h3>Kegiatan per Status</h3>
            <canvas id="activityStatusChart"></canvas>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h3>Tren Anggaran Kegiatan per Tahun</h3>
            <canvas id="activityBudgetChart"></canvas>
        </article>
    </section>

    <section>
        <div class="section-title">
            <h2>Daftar Kegiatan</h2>
            <span class="muted">Data terbaru kegiatan desa</span>
        </div>
        <div class="activity-list">
            @forelse($activities as $item)
                <article class="activity-item">
                    <div class="activity-cover">
                        <img src="{{ $item->cover_image_url ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=900&q=80' }}" alt="{{ $item->title }}">
                    </div>
                    <div class="activity-body">
                        <p class="activity-meta">
                            {{ $item->activity_date?->translatedFormat('d M Y') }} |
                            {{ $item->category }} |
                            {{ $item->status }}
                        </p>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ $item->summary ?: \Illuminate\Support\Str::limit(strip_tags((string) $item->description), 160) }}</p>
                        <div class="activity-footer">
                            <span>
                                Anggaran:
                                @if($item->budget !== null)
                                    Rp {{ number_format($item->budget, 0, ',', '.') }}
                                @else
                                    Tidak dicantumkan
                                @endif
                            </span>
                            <span>Lokasi: {{ $item->location }}</span>
                            @if($item->document_url)
                                <a href="{{ $item->document_url }}" target="_blank">Dokumen</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <article class="list-item">
                    <p>Belum ada data kegiatan desa.</p>
                </article>
            @endforelse
        </div>

        @if($activities->hasPages())
            <div class="list-pagination">
                <div class="pager-controls">
                    @if($activities->onFirstPage())
                        <span class="pager-link is-disabled">Sebelumnya</span>
                    @else
                        <a class="pager-link" href="{{ $activities->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    <span class="pager-meta">Halaman {{ $activities->currentPage() }} / {{ $activities->lastPage() }}</span>

                    @if($activities->hasMorePages())
                        <a class="pager-link" href="{{ $activities->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="pager-link is-disabled">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];

        const categoryLabels = @json($categoryChart['labels']);
        const categoryData = @json($categoryChart['data']);
        const categoryColors = categoryLabels.map((_, i) => palette[i % palette.length]);

        const statusLabels = @json($statusChart['labels']);
        const statusData = @json($statusChart['data']);
        const statusColors = statusLabels.map((_, i) => palette[i % palette.length]);

        const budgetLabels = @json($budgetYearChart['labels']);
        const budgetData = @json($budgetYearChart['data']);
        const budgetColors = budgetLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('activityCategoryChart'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Jumlah Kegiatan',
                    data: categoryData,
                    backgroundColor: categoryColors,
                    borderRadius: 7
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('activityStatusChart'), {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Jumlah Kegiatan',
                    data: statusData,
                    backgroundColor: statusColors,
                    borderRadius: 7
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('activityBudgetChart'), {
            type: 'bar',
            data: {
                labels: budgetLabels,
                datasets: [{
                    label: 'Total Anggaran',
                    data: budgetData,
                    backgroundColor: budgetColors,
                    borderRadius: 7
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
@endsection
