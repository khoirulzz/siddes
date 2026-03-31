@extends('layouts.dashboard')

@section('title', 'Manajemen Kependudukan')
@section('page_title', 'Manajemen Kependudukan')

@section('content')
    <section class="panel">
        <h2>Grafik Ringkasan Kependudukan</h2>
        <p class="muted">Ringkasan menyesuaikan filter dusun dan keyword search yang aktif.</p>
        <div class="stats">
            <article class="stat-card">
                <small>Total Penduduk (Filter Aktif)</small>
                <strong>{{ number_format($filteredTotal, 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Laki-laki</small>
                <strong>{{ number_format($genderSummary['Laki-laki'], 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Perempuan</small>
                <strong>{{ number_format($genderSummary['Perempuan'], 0, ',', '.') }}</strong>
            </article>
        </div>

        <div class="chart-grid-dashboard">
            <article class="chart-box">
                <h3>Distribusi Penduduk per Dusun</h3>
                <canvas id="populationHamletChart"></canvas>
            </article>
            <article class="chart-box">
                <h3>Komposisi Jenis Kelamin</h3>
                <canvas id="populationGenderChart"></canvas>
            </article>
        </div>
        <div class="chart-grid-dashboard" style="margin-top:0.8rem;">
            <article class="chart-box">
                <h3>Distribusi Usia Penduduk</h3>
                <canvas id="populationAgeChart"></canvas>
            </article>
            <article class="chart-box">
                <h3>Distribusi Pendidikan</h3>
                <canvas id="populationEducationChart" style="min-height:340px;"></canvas>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="toolbar">
            <h2>Tambah Data Kependudukan</h2>
            <div class="actions">
                <a class="btn btn-primary" href="{{ route('dashboard.population-records.create') }}">Tambah Manual (+)</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.population-records.template') }}">Download Template Import</a>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.population-records.import') }}" enctype="multipart/form-data" class="inline-form">
            @csrf
            <div class="field">
                <label for="file">Upload File Excel/CSV</label>
                <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
            </div>
            <button class="btn btn-primary" type="submit">Import Data</button>
        </form>
    </section>

    <section class="panel">
        <h2>Search Data</h2>
        <form method="GET" action="{{ route('dashboard.population-records.index') }}" class="inline-form">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <input type="hidden" name="hamlet" value="{{ $selectedHamlet !== 'Semua' ? $selectedHamlet : '' }}">
            <div class="field">
                <label for="q">Cari berdasarkan NIK / No KK / Nama</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: 3326010204010028">
            </div>
            <button class="btn btn-primary" type="submit">Cari</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.population-records.index', ['view' => $viewMode]) }}">Reset</a>
        </form>

        <div class="tabs" style="margin-top:0.8rem; margin-bottom:0;">
            <a class="tab {{ $viewMode === 'individual' ? 'active' : '' }}" href="{{ route('dashboard.population-records.index', ['view' => 'individual', 'hamlet' => $selectedHamlet !== 'Semua' ? $selectedHamlet : null, 'q' => $filters['q']]) }}">Tampilan Individu</a>
            <a class="tab {{ $viewMode === 'kk' ? 'active' : '' }}" href="{{ route('dashboard.population-records.index', ['view' => 'kk', 'hamlet' => $selectedHamlet !== 'Semua' ? $selectedHamlet : null, 'q' => $filters['q']]) }}">Tampilan Per KK</a>
        </div>

        <div class="tabs" style="margin-top:0.6rem; margin-bottom:0;">
            <a class="tab {{ $selectedHamlet === 'Semua' ? 'active' : '' }}" href="{{ route('dashboard.population-records.index', ['view' => $viewMode, 'q' => $filters['q']]) }}">Semua Dusun</a>
            @foreach($hamlets as $hamlet)
                <a
                    class="tab {{ $selectedHamlet === $hamlet ? 'active' : '' }}"
                    href="{{ route('dashboard.population-records.index', ['view' => $viewMode, 'hamlet' => $hamlet, 'q' => $filters['q']]) }}"
                >
                    {{ $hamlet }}
                </a>
            @endforeach
        </div>
    </section>

    @if($viewMode === 'kk')
        <section class="panel">
            <h2>Tabel Kependudukan Per KK</h2>
            <div class="table-wrap population-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No KK</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Alamat</th>
                            <th>Dusun</th>
                            <th>RT/RW</th>
                            <th>Total Anggota Aktif</th>
                            <th>Anggota Keluarga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($households as $household)
                            <tr>
                                <td>{{ $household->no_kk }}</td>
                                <td>{{ $household->nama_kepala_keluarga ?: '-' }}</td>
                                <td>{{ $household->alamat ?: '-' }}</td>
                                <td>{{ $household->dusun ?: '-' }}</td>
                                <td>{{ $household->rt ?: '-' }} / {{ $household->rw ?: '-' }}</td>
                                <td>{{ $household->total_members }}</td>
                                <td>
                                    @if($household->currentMembers->isNotEmpty())
                                        @foreach($household->currentMembers as $member)
                                            <div style="margin-bottom:0.22rem;">
                                                {{ $member->resident?->resolvedName() ?: '-' }}
                                                <small class="muted">({{ $member->resident?->nik ?: '-' }})</small>
                                            </div>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">Belum ada data KK.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($households->hasPages())
                <div class="table-pagination">
                    <small class="muted">
                        Menampilkan {{ $households->firstItem() ?? 0 }} - {{ $households->lastItem() ?? 0 }} dari {{ $households->total() }} data
                    </small>
                    <div class="pager-controls">
                        @if($households->onFirstPage())
                            <span class="pager-link is-disabled">Sebelumnya</span>
                        @else
                            <a class="pager-link" href="{{ $households->previousPageUrl() }}">Sebelumnya</a>
                        @endif

                        <span class="pager-meta">Halaman {{ $households->currentPage() }} / {{ $households->lastPage() }}</span>

                        @if($households->hasMorePages())
                            <a class="pager-link" href="{{ $households->nextPageUrl() }}">Berikutnya</a>
                        @else
                            <span class="pager-link is-disabled">Berikutnya</span>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    @else
        <section class="panel">
            <h2>Tabel Kependudukan Individu</h2>
            <div class="table-wrap population-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No Urut</th>
                            <th>Status Hubungan</th>
                            <th>Nama Lengkap</th>
                            <th>NIK</th>
                            <th>No KK</th>
                            <th>Nama Kepala Keluarga</th>
                            <th>Jenis Kelamin</th>
                            <th>Tempat Lahir</th>
                            <th>Tanggal Lahir</th>
                            <th>Agama</th>
                            <th>Pendidikan</th>
                            <th>Jenis Pekerjaan</th>
                            <th>Status Perkawinan</th>
                            <th>Kewarganegaraan</th>
                            <th>No Paspor</th>
                            <th>No KITAS/KITAP</th>
                            <th>Nama Ayah</th>
                            <th>Nama Ibu</th>
                            <th>Gol Darah</th>
                            <th>RT</th>
                            <th>RW</th>
                            <th>Dusun</th>
                            <th>Desa</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten</th>
                            <th>Provinsi</th>
                            <th>Kode Pos</th>
                            <th>Alamat Detail</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->currentMembership?->no_urut_kk ?: '-' }}</td>
                                <td>{{ $item->resolvedStatusHubungan() }}</td>
                                <td>{{ $item->resolvedName() }}</td>
                                <td>{{ $item->nik }}</td>
                                <td>{{ $item->resolvedKkNumber() }}</td>
                                <td>{{ $item->currentMembership?->household?->nama_kepala_keluarga ?: '-' }}</td>
                                <td>{{ $item->resolvedGender() }}</td>
                                <td>{{ $item->resolvedBirthPlace() }}</td>
                                <td>{{ $item->resolvedBirthDate()?->format('d-m-Y') ?: '-' }}</td>
                                <td>{{ $item->resolvedReligion() }}</td>
                                <td>{{ $item->pendidikan ?: '-' }}</td>
                                <td>{{ $item->resolvedOccupation() }}</td>
                                <td>{{ $item->status_perkawinan ?: '-' }}</td>
                                <td>{{ $item->kewarganegaraan ?: '-' }}</td>
                                <td>{{ $item->no_paspor ?: '-' }}</td>
                                <td>{{ $item->no_kitas_kitap ?: '-' }}</td>
                                <td>{{ $item->nama_ayah ?: '-' }}</td>
                                <td>{{ $item->nama_ibu ?: '-' }}</td>
                                <td>{{ $item->golongan_darah ?: '-' }}</td>
                                <td>{{ $item->resolvedRt() }}</td>
                                <td>{{ $item->resolvedRw() }}</td>
                                <td>{{ $item->resolvedHamlet() }}</td>
                                <td>{{ $item->resolvedVillage() }}</td>
                                <td>{{ $item->resolvedDistrict() }}</td>
                                <td>{{ $item->resolvedRegency() }}</td>
                                <td>{{ $item->resolvedProvince() }}</td>
                                <td>{{ $item->resolvedPostalCode() }}</td>
                                <td>{{ $item->address_detail ?: '-' }}</td>
                                <td>
                                    <div class="actions">
                                        <a class="btn btn-secondary" href="{{ route('dashboard.population-records.edit', $item) }}">Edit</a>
                                        <form action="{{ route('dashboard.population-records.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data penduduk ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="29">Belum ada data penduduk.</td></tr>
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
    @endif

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const hamletLabels = @json($summaryByHamlet->pluck('hamlet_name'));
        const hamletData = @json($summaryByHamlet->pluck('total'));
        const genderData = @json([$genderSummary['Laki-laki'], $genderSummary['Perempuan']]);
        const ageLabels = @json($ageSummary['labels']);
        const ageData = @json($ageSummary['data']);
        const educationLabels = @json($educationSummary['labels']);
        const educationData = @json($educationSummary['data']);
        const palette = ['#0f4c81', '#1f8a70', '#f59e0b', '#e76f51', '#457b9d', '#8ab17d', '#9d4edd'];
        const hamletColors = hamletLabels.map((_, i) => palette[i % palette.length]);
        const ageColors = ageLabels.map((_, i) => palette[i % palette.length]);
        const educationColors = educationLabels.map((_, i) => palette[i % palette.length]);

        const barOptions = {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        };

        new Chart(document.getElementById('populationHamletChart'), {
            type: 'bar',
            data: {
                labels: hamletLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: hamletData,
                    backgroundColor: hamletColors,
                    borderRadius: 8
                }]
            },
            options: barOptions
        });

        new Chart(document.getElementById('populationGenderChart'), {
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

        new Chart(document.getElementById('populationAgeChart'), {
            type: 'bar',
            data: {
                labels: ageLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: ageData,
                    backgroundColor: ageColors,
                    borderRadius: 8
                }]
            },
            options: barOptions
        });

        new Chart(document.getElementById('populationEducationChart'), {
            type: 'bar',
            data: {
                labels: educationLabels,
                datasets: [{
                    label: 'Jumlah Penduduk',
                    data: educationData,
                    backgroundColor: educationColors,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                    },
                },
            }
        });
    </script>
@endsection
