@extends('layouts.public')

@section('title', 'Informasi Publik - Pertanahan')

@section('content')
    <div class="section-title">
        <h2>Informasi Publik: Pertanahan</h2>
        <span class="muted">Ringkasan aset dan distribusi data pertanahan desa</span>
    </div>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Total Data Pertanahan</small>
            <strong>{{ number_format($totalRecords, 0, ',', '.') }} entri</strong>
        </article>
        <article class="summary-card">
            <small>Total Luas Terdata</small>
            <strong>{{ number_format($totalArea, 2, ',', '.') }} m2</strong>
        </article>
        <article class="summary-card">
            <small>Kategori Aset</small>
            <strong>{{ number_format($categorySummary->count(), 0, ',', '.') }}</strong>
        </article>
        <article class="summary-card">
            <small>Status Lahan</small>
            <strong>{{ number_format($statusSummary->count(), 0, ',', '.') }}</strong>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h3>Distribusi Kategori Lahan</h3>
            <canvas id="landByCategory"></canvas>
        </article>
        <article class="chart-card">
            <h3>Status Pertanahan</h3>
            <canvas id="landByStatus"></canvas>
        </article>
    </section>

    <section class="summary-note-card">
        <h3>Ringkasan Data</h3>
        <p>
            Halaman publik hanya menampilkan statistik pertanahan. Data detail per bidang,
            berkas, dan riwayat internal tersedia pada dashboard admin/operator.
        </p>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const categoryLabels = @json($categorySummary->pluck('category'));
        const categoryValues = @json($categorySummary->pluck('total'));
        const statusLabels = @json($statusSummary->pluck('status'));
        const statusValues = @json($statusSummary->pluck('total'));
        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];
        const categoryColors = categoryLabels.map((_, i) => palette[i % palette.length]);
        const statusColors = statusLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('landByCategory'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Jumlah Data',
                    data: categoryValues,
                    backgroundColor: categoryColors,
                    borderRadius: 6
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('landByStatus'), {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: statusColors,
                    borderRadius: 6
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
@endsection
