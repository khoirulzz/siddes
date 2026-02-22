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
            <h3>Distribusi Agama</h3>
            <canvas id="populationByReligion"></canvas>
        </article>
        <article class="chart-card">
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
                                <td>{{ $item->hamlet }}</td>
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
        const hamletLabels = @json($summaryByHamlet->pluck('hamlet'));
        const hamletData = @json($summaryByHamlet->pluck('total'));
        const genderData = @json([$genderSummary['Laki-laki'], $genderSummary['Perempuan']]);
        const religionLabels = @json($religionSummary->pluck('religion'));
        const religionData = @json($religionSummary->pluck('total'));

        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];
        const hamletColors = hamletLabels.map((_, i) => palette[i % palette.length]);
        const religionColors = religionLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('populationByHamlet'), {
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
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('populationByGender'), {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: genderData,
                    backgroundColor: ['#0f4c81', '#1f8a70']
                }]
            },
            options: { responsive: true }
        });

        new Chart(document.getElementById('populationByReligion'), {
            type: 'bar',
            data: {
                labels: religionLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: religionData,
                    backgroundColor: religionColors,
                    borderRadius: 6
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
@endsection
