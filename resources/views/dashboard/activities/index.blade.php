@extends('layouts.dashboard')

@section('title', 'Manajemen Kegiatan Desa')
@section('page_title', 'Manajemen Kegiatan Desa')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Daftar Kegiatan Desa</h2>
            <a class="btn btn-primary" href="{{ route('dashboard.village-activities.create') }}">Tambah Kegiatan (+)</a>
        </div>

        <div class="stats">
            <article class="stat-card">
                <small>Total Kegiatan</small>
                <strong>{{ number_format($totalItems, 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Total Anggaran Tercatat</small>
                <strong>Rp {{ number_format($totalBudget, 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Dengan Anggaran</small>
                <strong>{{ number_format($withBudget, 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Tanpa Anggaran</small>
                <strong>{{ number_format($withoutBudget, 0, ',', '.') }}</strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Filter Kegiatan Desa</h2>
        <form method="GET" action="{{ route('dashboard.village-activities.index') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari Judul / Lokasi / Penanggung Jawab</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: Musyawarah desa">
            </div>
            <div class="field">
                <label for="category">Kategori</label>
                <select id="category" name="category">
                    <option value="">Semua Kategori</option>
                    @foreach($categoryOptions as $option)
                        <option value="{{ $option }}" @selected($filters['category'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    @foreach($statusFilterOptions as $option)
                        <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="year">Tahun</label>
                <select id="year" name="year">
                    <option value="">Semua Tahun</option>
                    @foreach($yearOptions as $option)
                        <option value="{{ $option }}" @selected($filters['year'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.village-activities.index') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Grafik Kegiatan Desa</h2>
        <div class="chart-grid-dashboard">
            <article class="chart-box">
                <h3>Kategori Kegiatan</h3>
                <canvas id="activityCategoryChart"></canvas>
            </article>
            <article class="chart-box">
                <h3>Status Kegiatan</h3>
                <canvas id="activityStatusChart"></canvas>
            </article>
        </div>
        <div class="chart-grid-dashboard" style="margin-top:0.8rem;">
            <article class="chart-box">
                <h3>Anggaran per Tahun (Opsional)</h3>
                <canvas id="activityBudgetChart"></canvas>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Data Kegiatan</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>PJ</th>
                        <th>Status</th>
                        <th>Anggaran</th>
                        <th>File</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->activity_date?->format('d-m-Y') }}</td>
                            <td>{{ $item->title }}</td>
                            <td>{{ $item->category }}</td>
                            <td>{{ $item->location }}</td>
                            <td>{{ $item->person_in_charge ?: '-' }}</td>
                            <td>{{ $item->status }}</td>
                            <td>
                                @if($item->budget !== null)
                                    Rp {{ number_format($item->budget, 0, ',', '.') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($item->cover_image_url)
                                    <a href="{{ $item->cover_image_url }}" target="_blank">Cover</a>
                                @endif
                                @if($item->cover_image_url && $item->document_url)
                                    |
                                @endif
                                @if($item->document_url)
                                    <a href="{{ $item->document_url }}" target="_blank">Dokumen</a>
                                @endif
                                @if(!$item->cover_image_url && !$item->document_url)
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.village-activities.edit', $item) }}">Edit</a>
                                    <form action="{{ route('dashboard.village-activities.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data kegiatan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Belum ada data kegiatan desa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="table-pagination">
                <small class="muted">
                    Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
                </small>
                <div class="pager-controls">
                    @if($items->onFirstPage())
                        <span class="pager-link is-disabled">Sebelumnya</span>
                    @else
                        <a class="pager-link" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    <span class="pager-meta">Halaman {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>

                    @if($items->hasMorePages())
                        <a class="pager-link" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
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

        const categoryLabels = @json($categorySummary->pluck('category'));
        const categoryData = @json($categorySummary->pluck('total'));
        const categoryColors = categoryLabels.map((_, i) => palette[i % palette.length]);

        const statusLabels = @json($statusSummary->pluck('status'));
        const statusData = @json($statusSummary->pluck('total'));
        const statusColors = statusLabels.map((_, i) => palette[i % palette.length]);

        const budgetLabels = @json($yearlyBudgetSummary->pluck('year'));
        const budgetData = @json($yearlyBudgetSummary->pluck('total_budget'));
        const budgetColors = budgetLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('activityCategoryChart'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Jumlah Kegiatan',
                    data: categoryData,
                    backgroundColor: categoryColors,
                    borderRadius: 8
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
                    borderRadius: 8
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
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
@endsection
