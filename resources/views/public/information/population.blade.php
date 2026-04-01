@extends('layouts.public')

@section('title', 'Informasi Publik - Kependudukan')

@section('content')
    <div class="section-title">
        <h2>Informasi Publik: Kependudukan</h2>
        <span class="muted">Ringkasan penduduk tanpa menampilkan data pribadi detail</span>
    </div>

    <section class="summary-grid">
        <article class="summary-card">
            <small>Total Penduduk</small>
            <strong>{{ number_format($totalResidents, 0, ',', '.') }} jiwa</strong>
        </article>
        <article class="summary-card">
            <small>Total Dusun</small>
            <strong>{{ number_format($summaryByHamlet->count(), 0, ',', '.') }}</strong>
        </article>
        <article class="summary-card">
            <small>Laki-laki</small>
            <strong>{{ number_format($genderSummary['Laki-laki'], 0, ',', '.') }}</strong>
        </article>
        <article class="summary-card">
            <small>Perempuan</small>
            <strong>{{ number_format($genderSummary['Perempuan'], 0, ',', '.') }}</strong>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h3>Distribusi Penduduk per Dusun</h3>
            <canvas id="populationByHamlet"></canvas>
        </article>
        <article class="chart-card">
            <h3>Komposisi Jenis Kelamin</h3>
            <canvas id="populationByGender"></canvas>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card">
            <h3>Distribusi Rentang Usia</h3>
            <canvas id="populationByAgeRange"></canvas>
        </article>
        <article class="chart-card">
            <h3>Distribusi Pendidikan</h3>
            <canvas id="populationByEducation" class="chart-canvas-tall"></canvas>
        </article>
    </section>

    <section class="chart-grid">
        <article class="chart-card chart-card-wide">
            <h3>Ringkasan per Dusun</h3>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Dusun</th>
                            <th>Laki-laki</th>
                            <th>Perempuan</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaryByHamlet as $item)
                            <tr>
                                <td>{{ $item->hamlet_name }}</td>
                                <td>{{ number_format($item->male_total, 0, ',', '.') }}</td>
                                <td>{{ number_format($item->female_total, 0, ',', '.') }}</td>
                                <td>{{ number_format($item->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Belum ada data kependudukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const hamletLabels = @json($summaryByHamlet->pluck('hamlet_name'));
        const hamletData = @json($summaryByHamlet->pluck('total'));
        const genderData = @json([$genderSummary['Laki-laki'], $genderSummary['Perempuan']]);
        const ageRangeLabels = @json($ageSummary['labels']);
        const ageRangeData = @json($ageSummary['data']);
        const educationLabels = @json($educationSummary['labels']);
        const educationData = @json($educationSummary['data']);

        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];
        const hamletColors = hamletLabels.map((_, i) => palette[i % palette.length]);
        const ageRangeColors = ageRangeLabels.map((_, i) => palette[i % palette.length]);
        const educationColors = educationLabels.map((_, i) => palette[i % palette.length]);

        const getChartTheme = () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                text: isDark ? '#d5e5f6' : '#274761',
                grid: isDark ? 'rgba(139, 162, 185, 0.22)' : 'rgba(43, 74, 101, 0.12)',
            };
        };

        const barOptions = (theme) => ({
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { color: theme.grid },
                    ticks: { color: theme.text },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: theme.grid },
                    ticks: { color: theme.text },
                },
            },
        });

        const doughnutOptions = (theme) => ({
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: theme.text,
                    },
                },
            },
        });

        const horizontalBarOptions = (theme) => ({
            responsive: true,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: theme.grid },
                    ticks: { color: theme.text },
                },
                y: {
                    grid: { color: theme.grid },
                    ticks: { color: theme.text },
                },
            },
        });

        let chartTheme = getChartTheme();
        const hamletChart = new Chart(document.getElementById('populationByHamlet'), {
            type: 'bar',
            data: {
                labels: hamletLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: hamletData,
                    backgroundColor: hamletColors,
                    borderRadius: 6
                }]
            },
            options: barOptions(chartTheme),
        });

        const genderChart = new Chart(document.getElementById('populationByGender'), {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: genderData,
                    backgroundColor: ['#0f4c81', '#1f8a70']
                }]
            },
            options: doughnutOptions(chartTheme),
        });

        const ageRangeChart = new Chart(document.getElementById('populationByAgeRange'), {
            type: 'bar',
            data: {
                labels: ageRangeLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: ageRangeData,
                    backgroundColor: ageRangeColors,
                    borderRadius: 6
                }]
            },
            options: barOptions(chartTheme),
        });

        const educationChart = new Chart(document.getElementById('populationByEducation'), {
            type: 'bar',
            data: {
                labels: educationLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: educationData,
                    backgroundColor: educationColors,
                    borderRadius: 6
                }]
            },
            options: horizontalBarOptions(chartTheme),
        });

        const syncChartsWithTheme = () => {
            chartTheme = getChartTheme();
            hamletChart.options = barOptions(chartTheme);
            genderChart.options = doughnutOptions(chartTheme);
            ageRangeChart.options = barOptions(chartTheme);
            educationChart.options = horizontalBarOptions(chartTheme);
            hamletChart.update();
            genderChart.update();
            ageRangeChart.update();
            educationChart.update();
        };

        window.addEventListener('app:theme-change', syncChartsWithTheme);
    </script>
@endsection
