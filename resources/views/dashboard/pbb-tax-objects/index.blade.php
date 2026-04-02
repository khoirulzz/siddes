@extends('layouts.dashboard')

@section('title', 'Master Data PBB')
@section('page_title', 'Master Data PBB')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Master Data PBB per Tahun</h2>
            <div class="actions">
                <a class="btn btn-primary" href="{{ route('dashboard.pbb-tax-objects.create') }}">Tambah Data (+)</a>
                <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.template') }}">Download Template</a>
            </div>
        </div>

        <form method="POST" action="{{ route('dashboard.pbb-tax-objects.import') }}" enctype="multipart/form-data" class="inline-form">
            @csrf
            <div class="field">
                <label for="file">Upload File Excel/CSV</label>
                <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv,.txt" required>
                <small class="muted">Mendukung file .xlsx, .xls, .csv, .txt dengan pemisah koma (,) atau titik koma (;).</small>
            </div>
            <div class="field">
                <label for="year_override">Override Tahun (Opsional)</label>
                <input id="year_override" type="number" name="year_override" min="2026" max="{{ date('Y') + 1 }}" placeholder="Kosongkan agar ikut data file">
            </div>
            <button class="btn btn-primary" type="submit">Import Data</button>
        </form>

        <form method="POST" action="{{ route('dashboard.pbb-tax-objects.destroy-year') }}" class="inline-form" onsubmit="return confirm('Yakin hapus semua data PBB pada tahun yang dipilih? Proses ini tidak bisa dibatalkan.');">
            @csrf
            @method('DELETE')
            <div class="field">
                <label for="delete_year">Hapus Semua Data per Tahun</label>
                <select id="delete_year" name="year" required {{ $availableYears->isEmpty() ? 'disabled' : '' }}>
                    <option value="">Pilih Tahun</option>
                    @foreach($availableYears as $availableYear)
                        <option value="{{ $availableYear }}" {{ (int) ($filters['year'] ?? 0) === (int) $availableYear ? 'selected' : '' }}>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-danger" type="submit" {{ $availableYears->isEmpty() ? 'disabled' : '' }}>Hapus Data Tahun Terpilih</button>
        </form>
    </section>

    <section class="panel">
        <h2>Filter Data</h2>
        <form method="GET" action="{{ route('dashboard.pbb-tax-objects.index') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari NOP / Nama WP / Jalan WP / Jalan OP</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Contoh: 33.26.010.004.001-0028.0">
            </div>
            <div class="field">
                <label for="year">Tahun</label>
                <input id="year" type="number" name="year" value="{{ $filters['year'] ?? '' }}" min="2026" max="{{ date('Y') + 1 }}" placeholder="Semua tahun">
            </div>
            <button class="btn btn-primary" type="submit">Cari</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.index') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Tabel Master Data PBB</h2>
        <div class="table-wrap population-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>TAHUN</th>
                        <th>NOMOR OBJEK PAJAK (NOP)</th>
                        <th>NAMA WP SPPT</th>
                        <th>JALAN WP SPPT</th>
                        <th>RT WP SPPT</th>
                        <th>RW WP SPPT</th>
                        <th>DESA WP SPPT</th>
                        <th>JALAN OP SPPT</th>
                        <th>RT OP SPPT</th>
                        <th>RW OP SPPT</th>
                        <th>LUAS TANAH SPPT</th>
                        <th>LUAS BANGUNAN SPPT</th>
                        <th>PBB TERHUTANG</th>
                        <th>TANGGAL PEMBAYARAN</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxObjects as $item)
                        <tr>
                            <td>{{ ($taxObjects->firstItem() ?? 0) + $loop->index }}</td>
                            <td>{{ $item->resolvedTaxYear() }}</td>
                            <td>{{ $item->nop }}</td>
                            <td>{{ $item->nama_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->jalan_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->rt_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->rw_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->desa_wp_sppt ?: '-' }}</td>
                            <td>{{ $item->jalan_op_sppt ?: '-' }}</td>
                            <td>{{ $item->rt_op_sppt ?: '-' }}</td>
                            <td>{{ $item->rw_op_sppt ?: '-' }}</td>
                            <td>{{ number_format($item->resolvedLandArea(), 2, ',', '.') }}</td>
                            <td>{{ number_format($item->resolvedBuildingArea(), 2, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->resolvedAmountDue(), 0, ',', '.') }}</td>
                            <td>{{ $item->resolvedPaidAt()?->format('d-m-Y') ?: '-' }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.show', $item) }}">Detail</a>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.pbb-tax-objects.edit', $item) }}">Edit</a>
                                    <form method="POST" action="{{ route('dashboard.pbb-tax-objects.destroy', $item) }}" onsubmit="return confirm('Yakin hapus data ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="16">Belum ada data PBB.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($taxObjects->hasPages())
            <div class="table-pagination">
                <small class="muted">
                    Menampilkan {{ $taxObjects->firstItem() ?? 0 }} - {{ $taxObjects->lastItem() ?? 0 }} dari {{ $taxObjects->total() }} data
                </small>
                <div class="pager-controls">
                    @if($taxObjects->onFirstPage())
                        <span class="pager-link is-disabled">Sebelumnya</span>
                    @else
                        <a class="pager-link" href="{{ $taxObjects->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    <span class="pager-meta">Halaman {{ $taxObjects->currentPage() }} / {{ $taxObjects->lastPage() }}</span>

                    @if($taxObjects->hasMorePages())
                        <a class="pager-link" href="{{ $taxObjects->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="pager-link is-disabled">Berikutnya</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endsection
