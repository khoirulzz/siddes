@extends('layouts.dashboard')

@section('title', 'Layanan PBB')
@section('page_title', 'Layanan Pembayaran PBB')

@section('content')
    <section class="panel">
        <h2>Ringkasan Pengajuan PBB</h2>
        <div class="stats">
            <article class="stat-card"><small>Total Pengajuan</small><strong>{{ $stats['total'] }}</strong></article>
            <article class="stat-card"><small>Diajukan</small><strong>{{ $stats['diajukan'] }}</strong></article>
            <article class="stat-card"><small>Diproses</small><strong>{{ $stats['proses'] }}</strong></article>
            <article class="stat-card"><small>Selesai</small><strong>{{ $stats['selesai'] }}</strong></article>
        </div>
    </section>

    <section class="panel">
        <h2>Filter Pengajuan</h2>
        <form method="GET" action="{{ route('dashboard.pbb-payment-requests.index') }}" class="inline-form">
            <div class="field">
                <label for="q">Cari Tiket / Nama / WA / NOP</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: PBB-260218-ABCD">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a class="btn btn-secondary" href="{{ route('dashboard.pbb-payment-requests.index') }}">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Daftar Pengajuan PBB</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nomor Tiket</th>
                        <th>Tanggal</th>
                        <th>Pemohon</th>
                        <th>Kontak</th>
                        <th>Total NOP</th>
                        <th>Total Nominal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $ticket = $item->ticket_code;
                            if (! $ticket && $item->notes && preg_match('/TIKET:([A-Z0-9\-]+)/i', (string) $item->notes, $matches) === 1) {
                                $ticket = strtoupper((string) $matches[1]);
                            }
                        @endphp
                        <tr>
                            <td><strong>{{ $ticket ?: '-' }}</strong></td>
                            <td>{{ $item->submitted_at?->format('d-m-Y H:i') ?: '-' }}</td>
                            <td>{{ $item->applicant_name }}</td>
                            <td>
                                {{ $item->phone }}<br>
                                <small>{{ $item->email ?: '-' }}</small>
                            </td>
                            <td>{{ $item->totalNops() }}</td>
                            <td>Rp {{ number_format((float) $item->amount_due, 0, ',', '.') }}</td>
                            <td><span class="status-badge">{{ $item->status }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.pbb-payment-requests.show', $item) }}">Detail</a>
                                    <form action="{{ route('dashboard.pbb-payment-requests.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus data pengajuan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Belum ada pengajuan pembayaran PBB.</td></tr>
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
@endsection
