@extends('layouts.dashboard')

@section('title', 'Layanan Pengaduan')
@section('page_title', 'Layanan Pengaduan Masyarakat')

@section('content')
    <section class="panel">
        <h2>Ringkasan Pengaduan</h2>
        <div class="stats">
            <article class="stat-card"><small>Total Pengaduan</small><strong>{{ $stats['total'] }}</strong></article>
            <article class="stat-card"><small>Diterima</small><strong>{{ $stats['diterima'] }}</strong></article>
            <article class="stat-card"><small>Diproses</small><strong>{{ $stats['proses'] }}</strong></article>
            <article class="stat-card"><small>Selesai</small><strong>{{ $stats['selesai'] }}</strong></article>
        </div>
    </section>

    <section class="panel">
        <h2>Daftar Pengaduan</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tiket</th>
                        <th>Tanggal</th>
                        <th>Pelapor / NIK</th>
                        <th>Kategori</th>
                        <th>Subjek</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th>Respon</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->ticket_code }}</td>
                            <td>{{ $item->submitted_at?->format('d-m-Y H:i') }}</td>
                            <td>
                                {{ $item->reporter_name }}<br>
                                <small>NIK: {{ $item->nik ?: '-' }}</small><br>
                                <small>{{ $item->phone }}</small>
                            </td>
                            <td>{{ $item->category }}</td>
                            <td>{{ $item->subject }}</td>
                            <td>
                                @if($item->evidence_url)
                                    <a href="{{ route('dashboard.complaint-reports.evidence', $item) }}" target="_blank">Lihat</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td><span class="status-badge">{{ $item->status }}</span></td>
                            <td>{{ $item->response ?: '-' }}</td>
                            <td>
                                <form id="complaint-delete-form-{{ $item->id }}" action="{{ route('dashboard.complaint-reports.destroy', $item) }}" method="POST" class="hidden-inline-form" onsubmit="return confirm('Hapus data pengaduan ini?')">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <div class="service-row-actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.complaint-reports.show', $item) }}">Detail</a>
                                    <button class="btn btn-danger icon-btn" type="submit" form="complaint-delete-form-{{ $item->id }}" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-1 0v14H9V6h6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Belum ada pengaduan masuk.</td></tr>
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
