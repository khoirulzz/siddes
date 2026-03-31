@extends('layouts.dashboard')

@section('title', 'Riwayat Transaksi Pertanahan')
@section('page_title', 'Riwayat Transaksi Pertanahan')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Riwayat Berdasarkan Nama/Halaman</h2>
            <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.index') }}">Kembali ke Transaksi</a>
        </div>

        <form method="GET" action="{{ route('dashboard.land-transactions.history') }}" class="inline-form">
            <div class="field">
                <label for="name">Nama / Istri / Pengenal</label>
                <input id="name" type="text" name="name" value="{{ $filters['name'] }}" placeholder="Contoh: Sukirman atau nama istri">
            </div>
            <div class="field">
                <label for="page">Halaman Buku C</label>
                <input id="page" type="text" name="page" value="{{ $filters['page'] }}" placeholder="Contoh: 500">
            </div>
            <button class="btn btn-primary" type="submit">Cari Riwayat</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.history') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Hasil Riwayat</h2>
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
                        <th>Arsip</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                            <td>{{ $item->transaction_number }}</td>
                            <td>{{ $item->transaction_date?->format('d-m-Y') ?: '-' }}</td>
                            <td>{{ $item->type_label }}</td>
                            <td>
                                {{ $item->party_a_name }} (Hal. {{ $item->party_a_page }})
                                @if($item->party_a_identifier)
                                    <br>
                                    <small class="muted">{{ $item->party_a_identifier }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $item->party_b_name }} (Hal. {{ $item->party_b_page }})
                                @if($item->party_b_identifier)
                                    <br>
                                    <small class="muted">{{ $item->party_b_identifier }}</small>
                                @endif
                            </td>
                            <td>{{ number_format($item->files_count ?? 0, 0, ',', '.') }} file</td>
                            <td>
                                <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.show', $item) }}">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Belum ada transaksi yang sesuai dengan filter riwayat.</td>
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
