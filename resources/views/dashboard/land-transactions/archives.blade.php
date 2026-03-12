@extends('layouts.dashboard')

@section('title', 'Arsip Dokumen Pertanahan')
@section('page_title', 'Arsip Dokumen Pertanahan')

@section('content')
    <section class="panel">
        <div class="toolbar">
            <h2>Arsip Dokumen Transaksi</h2>
            <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.index') }}">Kembali ke Transaksi</a>
        </div>

        <form method="GET" action="{{ route('dashboard.land-transactions.archives') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari File / Nomor / Nama / Halaman</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: AJB atau TNH-260312-ABCD">
            </div>
            <div class="field">
                <label for="kind">Jenis File</label>
                <select id="kind" name="kind">
                    <option value="">Semua File</option>
                    <option value="pdf" @selected($filters['kind'] === 'pdf')>PDF</option>
                    <option value="image" @selected($filters['kind'] === 'image')>Gambar</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.archives') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Arsip</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>File</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th>Transaksi</th>
                        <th>Pihak</th>
                        <th>Tanggal Upload</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $file)
                        <tr>
                            <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                            <td>{{ $file->original_name ?: basename((string) $file->file_path) }}</td>
                            <td>{{ $file->mime_type ?: '-' }}</td>
                            <td>{{ $file->size_bytes ? number_format($file->size_bytes / 1024, 1, ',', '.') . ' KB' : '-' }}</td>
                            <td>
                                @if($file->transaction)
                                    <a href="{{ route('dashboard.land-transactions.show', $file->transaction) }}">
                                        {{ $file->transaction->transaction_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($file->transaction)
                                    {{ $file->transaction->party_a_name }} ({{ $file->transaction->party_a_page }})
                                    <br>
                                    {{ $file->transaction->party_b_name }} ({{ $file->transaction->party_b_page }})
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $file->created_at?->format('d-m-Y H:i') ?: '-' }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.files.show', $file) }}" target="_blank">Lihat</a>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.land-transactions.files.show', ['landTransactionFile' => $file, 'mode' => 'download']) }}">Unduh</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">Belum ada arsip dokumen transaksi pertanahan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="table-pagination">
                <small class="muted">
                    Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} arsip
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

