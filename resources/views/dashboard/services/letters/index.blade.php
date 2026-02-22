@extends('layouts.dashboard')

@section('title', 'Layanan Surat Online')
@section('page_title', 'Layanan Surat Online')

@section('content')
    <section class="panel">
        <h2>Ringkasan Pengajuan Surat</h2>
        <div class="stats">
            <article class="stat-card"><small>Total Pengajuan</small><strong>{{ $stats['total'] }}</strong></article>
            <article class="stat-card"><small>Surat Masuk</small><strong>{{ $stats['masuk'] }}</strong></article>
            <article class="stat-card"><small>Diproses</small><strong>{{ $stats['proses'] }}</strong></article>
            <article class="stat-card"><small>Selesai</small><strong>{{ $stats['selesai'] }}</strong></article>
            <article class="stat-card"><small>Ditolak</small><strong>{{ $stats['ditolak'] }}</strong></article>
        </div>
    </section>

    <section class="panel">
        <h2>Daftar Pengajuan Surat</h2>
        <p class="muted">Nomor surat resmi tersimpan otomatis dengan format urut 3 digit per jenis surat. Admin dapat mengubah status dan mengunduh dokumen PDF atau DOCX.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nomor Resmi</th>
                        <th>Nomor Tiket</th>
                        <th>Tanggal</th>
                        <th>Pemohon</th>
                        <th>Jenis Surat</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th>Dokumen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->official_number ?: '-' }}</strong>
                            </td>
                            <td><small>{{ $item->ticket_number }}</small></td>
                            <td>{{ $item->submitted_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td>
                                {{ $item->applicant_name ?: '-' }}<br>
                                <small>{{ $item->nik ?: '-' }}</small>
                            </td>
                            <td>{{ $item->letter_type }}</td>
                            <td>
                                {{ $item->phone ?: '-' }}<br>
                                <small>{{ $item->email ?: '-' }}</small>
                            </td>
                            <td><span class="status-badge">{{ $item->status }}</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-secondary" href="{{ route('dashboard.letter-service-requests.download', ['letterServiceRequest' => $item, 'format' => 'pdf']) }}">PDF</a>
                                    <a class="btn btn-secondary" href="{{ route('dashboard.letter-service-requests.download', ['letterServiceRequest' => $item, 'format' => 'docx']) }}">DOCX</a>
                                </div>
                            </td>
                            <td>
                                <form id="letter-update-form-{{ $item->id }}" method="POST" action="{{ route('dashboard.letter-service-requests.update', $item) }}" class="service-inline-form service-inline-form--compact">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status">
                                        @foreach($statusOptions as $status)
                                            <option value="{{ $status }}" @selected($item->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="admin_notes" value="{{ $item->admin_notes }}" placeholder="Catatan admin">
                                </form>

                                <form id="letter-delete-form-{{ $item->id }}" action="{{ route('dashboard.letter-service-requests.destroy', $item) }}" method="POST" class="hidden-inline-form" onsubmit="return confirm('Hapus data pengajuan ini?')">
                                    @csrf
                                    @method('DELETE')
                                </form>

                                <div class="service-row-actions">
                                    <button class="btn btn-primary icon-btn" type="submit" form="letter-update-form-{{ $item->id }}" title="Update">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button class="btn btn-danger icon-btn" type="submit" form="letter-delete-form-{{ $item->id }}" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-1 0v14H9V6h6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Belum ada pengajuan surat online.</td></tr>
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
