@extends('layouts.dashboard')

@section('title', 'Transaksi Pertanahan')
@section('page_title', 'Transaksi Pertanahan')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <div>
                <h2>Pencatatan Transaksi Letter C</h2>
                <p class="muted" style="margin:0.2rem 0 0;">Fokus pada transaksi baru dan arsip bukti dokumen.</p>
            </div>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.archives') }}">Arsip Dokumen</a>
                <a class="btn btn-primary" href="{{ route('dashboard.land-transactions.create') }}">Catat Transaksi (+)</a>
            </div>
        </div>

        <div class="stats">
            <article class="stat-card">
                <small>Total Transaksi</small>
                <strong>{{ number_format($stats['total_rows'], 0, ',', '.') }}</strong>
            </article>
            <article class="stat-card">
                <small>Total Luas Tercatat</small>
                <strong>{{ number_format($stats['total_area'], 2, ',', '.') }} m2</strong>
            </article>
            <article class="stat-card">
                <small>Total Arsip File</small>
                <strong>{{ number_format($stats['total_files'], 0, ',', '.') }}</strong>
            </article>
        </div>
    </section>

    <section class="panel">
        <h2>Filter Transaksi</h2>
        <form method="GET" action="{{ route('dashboard.land-transactions.index') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari Nomor / Nama / Pengenal / Alamat / Halaman / Objek</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: Sukirman, istri, atau alamat pihak">
            </div>
            <div class="field">
                <label for="type">Jenis Transaksi</label>
                <select id="type" name="type">
                    <option value="">Semua Jenis</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="from">Tanggal Dari</label>
                <input id="from" type="date" name="from" value="{{ $filters['from'] }}">
            </div>
            <div class="field">
                <label for="to">Tanggal Sampai</label>
                <input id="to" type="date" name="to" value="{{ $filters['to'] }}">
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.index') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Transaksi</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nomor Transaksi</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Pihak A</th>
                        <th>Pihak B</th>
                        <th>Objek Tanah</th>
                        <th>Luas (m2)</th>
                        <th>Arsip</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->transaction_number }}</strong>
                                @if($item->document_number)
                                    <br>
                                    <small class="muted">Dok: {{ $item->document_number }}</small>
                                @endif
                            </td>
                            <td>{{ $item->transaction_date?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $item->type_label }}</td>
                            <td>
                                <a href="{{ route('dashboard.land-transactions.history', ['name' => $item->party_a_name, 'page' => $item->party_a_page]) }}">
                                    {{ $item->party_a_name }}
                                </a>
                                @if($item->party_a_identifier)
                                    <br>
                                    <small class="muted">Istri/Pengenal: {{ $item->party_a_identifier }}</small>
                                @endif
                                <br>
                                <small class="muted">Hal. {{ $item->party_a_page }}</small>
                            </td>
                            <td>
                                <a href="{{ route('dashboard.land-transactions.history', ['name' => $item->party_b_name, 'page' => $item->party_b_page]) }}">
                                    {{ $item->party_b_name }}
                                </a>
                                @if($item->party_b_identifier)
                                    <br>
                                    <small class="muted">Istri/Pengenal: {{ $item->party_b_identifier }}</small>
                                @endif
                                <br>
                                <small class="muted">Hal. {{ $item->party_b_page }}</small>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($item->land_object, 90) }}</td>
                            <td>{{ $item->area_m2 !== null ? number_format((float) $item->area_m2, 2, ',', '.') : '-' }}</td>
                            <td>{{ number_format($item->files_count ?? 0, 0, ',', '.') }} file</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.show', $item) }}">Detail</a>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.edit', $item) }}">Edit</a>
                                    <form action="{{ route('dashboard.land-transactions.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus transaksi ini beserta arsipnya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">Belum ada transaksi pertanahan tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="table-pagination">
                <small class="muted">
                    Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} transaksi
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
@endsection
