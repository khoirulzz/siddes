@extends('layouts.dashboard')

@section('title', 'Manajemen Pertanahan')
@section('page_title', 'Manajemen Pertanahan')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Data Pertanahan Lengkap</h2>
            <a class="btn btn-primary" href="{{ route('dashboard.land-records.create') }}">Tambah Data (+)</a>
        </div>

        <div class="stats">
            <article class="stat-card">
                <small>Total Entri Pertanahan</small>
                <strong>{{ number_format($totalItems, 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Total Luas Terdata</small>
                <strong>{{ number_format($totalArea, 2, ',', '.') }} m2</strong>
            </article>
            <article class="stat-card">
                <small>Kategori Lahan</small>
                <strong>{{ number_format($categorySummary->count(), 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Status Lahan</small>
                <strong>{{ number_format($statusSummary->count(), 0, ',', '.') }}</strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Grafik Pertanahan</h2>
        <div class="chart-grid-dashboard">
            <article class="chart-box">
                <h3>Distribusi Kategori</h3>
                <canvas id="landCategoryChart"></canvas>
            </article>
            <article class="chart-box">
                <h3>Distribusi Status</h3>
                <canvas id="landStatusChart"></canvas>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Filter Data Pertanahan</h2>
        <form method="GET" action="{{ route('dashboard.land-records.index') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari Kode / Lokasi / Pemilik / Sertifikat / NOP</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: C.123 atau NOP">
            </div>
            <div class="field">
                <label for="hamlet">Dusun</label>
                <select id="hamlet" name="hamlet">
                    <option value="">Semua Dusun</option>
                    @foreach($hamletOptions as $option)
                        <option value="{{ $option }}" @selected($filters['hamlet'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $option)
                        <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.land-records.index') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Data Pertanahan</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Lokasi</th>
                        <th>Dusun</th>
                        <th>Kategori</th>
                        <th>Luas (m2)</th>
                        <th>Kepemilikan</th>
                        <th>Pemilik</th>
                        <th>Sertifikat/NOP</th>
                        <th>Status</th>
                        <th>File</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->land_code ?? '-' }}</td>
                            <td>{{ $item->location }}</td>
                            <td>{{ $item->hamlet ?? '-' }}</td>
                            <td>{{ $item->category }}</td>
                            <td>{{ number_format($item->area_m2, 2, ',', '.') }}</td>
                            <td>{{ $item->ownership_status }}</td>
                            <td>{{ $item->owner_name ?? '-' }}</td>
                            <td>
                                {{ $item->certificate_number ?? '-' }} /
                                {{ $item->tax_object_number ?? '-' }}
                            </td>
                            <td>{{ $item->status }}</td>
                            <td>
                                @if($item->photo_url)
                                    <a href="{{ $item->photo_url }}" target="_blank">Foto</a>
                                @endif
                                @if($item->photo_url && $item->document_url)
                                    |
                                @endif
                                @if($item->document_url)
                                    <a href="{{ $item->document_url }}" target="_blank">Dokumen</a>
                                @endif
                                @if(!$item->photo_url && !$item->document_url)
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-records.edit', $item) }}">Edit</a>
                                    <form action="{{ route('dashboard.land-records.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data pertanahan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11">Belum ada data pertanahan.</td></tr>
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
        const categoryLabels = @json($categorySummary->keys()->values());
        const categoryData = @json($categorySummary->values());
        const statusLabels = @json($statusSummary->keys()->values());
        const statusData = @json($statusSummary->values());
        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];

        const categoryColors = categoryLabels.map((_, i) => palette[i % palette.length]);
        const statusColors = statusLabels.map((_, i) => palette[i % palette.length]);

        new Chart(document.getElementById('landCategoryChart'), {
            type: 'bar',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Jumlah Data',
                    data: categoryData,
                    backgroundColor: categoryColors,
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('landStatusChart'), {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Jumlah Data',
                    data: statusData,
                    backgroundColor: statusColors,
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
@endsection
